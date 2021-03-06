<?php

define("BLANK_FILE", "blank.pdf");
require_once APPPATH . "libraries/MPDF/mpdf.php";

class Reservierung_Controller extends MY_Controller
{

    public function create_voucher()
    {
        $persons = explode(',', $this->input->post('persons'));
        if (!$persons)
            exit();

        $url = $this->do_voucher($this->input->post('item_id'), $this->input->post('item_type'), $persons, $this->input->post('incoming_id'));

        echo $url;
        exit();
    }

    private function do_voucher($item_id, $item_type, $persons, $incoming_id)
    {
        $item = $item_type == "hotel" ? FormularHotel::find_by_id($item_id) : FormularManuel::find_by_id($item_id);
        $formular = Formular::find_by_id($item->formular_id);

        if (!$formular)
            return;

        $this->view_data['formular'] = $formular;
        $this->view_data['item'] = $item;
        $this->view_data['incoming'] = Kunde::find_by_id($incoming_id);
        $this->view_data['persons'] = array();
        foreach ($persons as $person_id)
            $this->view_data['persons'][] = FormularPerson::find_by_id($person_id);

        $pdf = new mPDF('utf-8', 'A4', '8', '', 0, 0, 0, 0, 0, 0);
        $pdf->SetImportUse();
        $pdf->AddPage();
        $pagecount = $pdf->SetSourceFile("voucher_blank.pdf");
        $tplId = $pdf->ImportPage($pagecount);
        $pdf->UseTemplate($tplId);

        $view = "";

        if ($item->type == "hotel")
            $view = "hotel";
        else
            $view = "manuel";

        $html = $this->load->view("vouchers/" . $view, $this->view_data, TRUE);

        $stylesheet = file_get_contents('css/voucher.css');
        $pdf->WriteHTML($stylesheet, 1);
        $pdf->list_indent_first_level = 0;
        $pdf->WriteHTML($html, 2);

        $pdf->Output("pdf/" . $item->voucher_name, 'F');
        return "pdf/" . $item->voucher_name;

    }

    private function write_pdf($formular_id)
    {
        $formular = Formular::find_by_id($formular_id);

        $this->write_to_pdf($formular_id, 1);
        $this->write_to_pdf($formular_id, 2);

        if ($formular->status == "rechnung" || $formular->status == "freigabe") {
            $this->write_to_pdf($formular_id, 3);
            $this->write_to_pdf($formular_id, 4);
        }

    }

    private function write_to_pdf($formular_id, $type)
    {
        $formular = Formular::find_by_id($formular_id);

        if (!$formular)
            return;

        $this->view_data['formular'] = $formular;

        $pdf = new mPDF('utf-8', 'A4', '8', '', 4, 4, 25, 25, 0, 0);
        $pdf->SetImportUse();
        $pdf->AddPage();
        $pagecount = $pdf->SetSourceFile(BLANK_FILE);
        $tplId = $pdf->ImportPage($pagecount);
        $pdf->UseTemplate($tplId);

        $view = "";
        if ($type == 1)
            $view = "angebot";
        elseif ($type == 2)
            $view = "angebotK";
        elseif ($type == 3)
            $view = "rechnung";
        elseif ($type == 4)
            $view = "rechnungK";
        elseif ($type == 5)
            $view = "storeno";
        elseif ($type == 6)
            $view = "storenoK";

        $html = $this->load->view("pdf_reports/" . $view, $this->view_data, TRUE);

        $stylesheet = file_get_contents('css/pdf.css');
        $pdf->WriteHTML($stylesheet, 1);
        $pdf->list_indent_first_level = 0;
        $pdf->WriteHTML($html, 2);

        $pdf->Output('pdf/' . $formular->id . "_" . $type . ".pdf", 'F');

    }

    public function get_pdf_name($formular_id)
    {
        $formular = Formular::find_by_id($formular_id);
        if (!$formular)
            return "NONAME";

        switch ($formular->status) {
            case "angebot":
                return "Angebot-" . ($formular->persons ? $formular->persons[0]->name : '') . "-UniqueWorld";
            case "rechnung":
                return "Rechnung-" . str_replace('/', '', $formular->r_num) . "-UniqueWorld";
            case "storno":
                return "Storno-" . str_replace('/', '', $formular->r_num) . "-UniqueWorld";
        }
    }


    public function __construct()
    {
        parent::__construct();

        if (!$this->user)
            redirect('');

        $this->view_data['JS_files'] = array("js/reservierung.js");

        $this->load->helper('date');
    }

    public function generate_vnum($type = "")
    {
        $val = "";

        if ($type == "bausteinreise") {
            $next_bnum = Config::find_by_param('next_bausteinreise_vnum');
            $val = "B" . $next_bnum->value++;
            $next_bnum->save();
        }

        echo $val;
        exit();
    }

    private function get_prepayment_date()
    {
        $today = getdate(time());
        return mktime(0, 0, 0, $today['mon'], $today['mday'] + 2, $today['year']);
    }

    public function create($kunde_id = 0)
    {
        if ($kunde_id == 0) {

            $this->set_page_tpl("no_kunde");

            if ($_POST) {
                $kunde_id = $this->input->post('kunde_id');
                $kunde = Kunde::find_by_k_num($kunde_id);

                if (!$kunde) {
                    $this->view_data['error'] = 'Client mit der Nummer "' . $kunde_id . '" wurde nicht gefunden';
                }
                else
                {
                    redirect("reservierung/create/" . $kunde->id);
                }
            }

        }
        else
        {

            $kunde = Kunde::find_by_id($kunde_id);

            if (!$kunde) {
                show_404();
                return;
            }

            if ($_POST) {

                $formular = Formular::create(array(
                        'v_num' => $this->input->post('formular-vnum'),
                        'kunde_id' => $this->input->post('kunde_id'),
                        'created_date' => date('Y-m-d', time()),
                        'type' => $this->input->post('formular-type'),
                        'r_num' => 0,
                        'provision' => $this->input->post('provision'),
                        'flight_text' => $this->input->post('flight-text'),
                        'flight_price' => $this->input->post('flight-price'),
                        'person_count' => $this->input->post('personcount'),
                        'sachbearbeiter' => $this->user->name . " " . $this->user->surname,
                        'prepayment_date' => time_to_mysqldate($this->get_prepayment_date())
                    )
                );


                if (isset($_POST['hotelname']) && is_array($_POST['hotelname']))
                    foreach ($_POST['hotelname'] as $ind => $hotel_name)
                    {
                        $is_manuel = !isset($_POST['hotelcode'][$ind]);
                        $hotel = $is_manuel ? null : Hotel::find_by_code($_POST['hotelcode'][$ind]);
                        $hotel_id = $hotel ? $hotel->id : 0;
                        FormularHotel::create(array(
                            'formular_id' => $formular->id,
                            'status' => 'none',
                            'hotel_id' => $hotel_id,
                            'hotel_name' => $hotel_name,
                            'roomcapacity' => $_POST['roomcapacity'][$ind],
                            'roomtype' => $_POST['roomtype'][$ind],
                            'hotelservice_id' => $_POST['service'][$ind],
                            'date_start' => inputdate_to_mysqldate($_POST['datestart'][$ind]),
                            'date_end' => inputdate_to_mysqldate($_POST['dateend'][$ind]),
                            'price' => $_POST['price'][$ind],
                            'days_count' => $_POST['dayscount'][$ind],
                            'transfer' => $_POST['transfer'][$ind],
                            'transfer_price' => $_POST['transfer_price'][$ind],
                            'remark' => $_POST['remark'][$ind],
                            'voucher_remark' => $_POST['voucher_remark'][$ind],
                            'city_tour' => $_POST['city_tour'][$ind],
                        ));
                    }

                if (isset($_POST['manuel_text']) && is_array($_POST['manuel_text']))
                    foreach ($this->input->post('manuel_text') as $ind => $manuel_text)
                    {
                        FormularManuel::create(array(
                            'formular_id' => $formular->id,
                            'status' => 'none',
                            'text' => $manuel_text,
                            'date_start' => isset($_POST['manuel_datestart'][$ind]) ? inputdate_to_mysqldate($_POST['manuel_datestart'][$ind]) : '',
                            'date_end' => isset($_POST['manuel_dateend'][$ind]) ? inputdate_to_mysqldate($_POST['manuel_dateend'][$ind]) : '',
                            'days_count' => isset($_POST['manuel_dayscount'][$ind]) ? $_POST['manuel_dayscount'][$ind] : 0,
                            'price' => $_POST['manuel_price'][$ind],
                            'voucher_remark' => $_POST['manuel_voucher_remark'][$ind],
                        ));
                    }

                redirect('reservierung/result/' . $formular->id);
            }
            else
            {
                $this->view_data['kunde'] = $kunde;
            }
            $this->set_page_tpl("kunde");
        }
    }

    public function edit($id = 0)
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            show_404();
            return false;
        }
        if ($_POST) {

            $formular->provision = $this->input->post('provision');
            $formular->flight_text = $this->input->post('flight-text');
            $formular->flight_price = $this->input->post('flight-price');
            $formular->person_count = $this->input->post('personcount');

            $formular->save();

            $formular_exist = array();
            foreach ($formular->hotels_and_manuels as $item)
                $formular_exist[$item->id] = array("type" => $item->type, "value" => 0);

            if (isset($_POST['hotelname']) && is_array($_POST['hotelname']))
                foreach ($_POST['hotelname'] as $ind => $hotel_name)
                {
                    $is_manuel = isset($_POST['hotelcode'][$ind]);
                    $hotel = !$is_manuel ? null : Hotel::find_by_code($_POST['hotelcode'][$ind]);
                    $hotel_id = $hotel ? $hotel->id : 0;

                    if (isset($_POST['formular_hotel_id'][$ind])) {
                        $hotel = FormularHotel::find_by_id($_POST['formular_hotel_id'][$ind]);

                        $hotel->roomcapacity = $_POST['roomcapacity'][$ind];
                        $hotel->roomtype = $_POST['roomtype'][$ind];
                        $hotel->hotelservice_id = $_POST['service'][$ind];
                        $hotel->date_start = inputdate_to_mysqldate($_POST['datestart'][$ind]);
                        $hotel->date_end = inputdate_to_mysqldate($_POST['dateend'][$ind]);
                        $hotel->days_count = $_POST['dayscount'][$ind];
                        $hotel->price = $_POST['price'][$ind];
                        $hotel->transfer = $_POST['transfer'][$ind];
                        $hotel->transfer_price = $_POST['transfer_price'][$ind];
                        $hotel->remark = $_POST['remark'][$ind];
                        $hotel->hotel_id = $hotel_id;
                        $hotel->hotel_name = $_POST['hotelname'][$ind];
                        $hotel->voucher_remark = $_POST['voucher_remark'][$ind];
                        $hotel->city_tour = $_POST['city_tour'][$ind];

                        $hotel->save();

                        $formular_exist[$hotel->id]['value'] = 1;
                    }
                    else
                    {
                        FormularHotel::create(array(
                            'formular_id' => $formular->id,
                            'status' => 'none',
                            'hotel_id' => $hotel_id,
                            'hotel_name' => $hotel_name,
                            'roomcapacity' => $_POST['roomcapacity'][$ind],
                            'roomtype' => $_POST['roomtype'][$ind],
                            'hotelservice_id' => $_POST['service'][$ind],
                            'date_start' => inputdate_to_mysqldate($_POST['datestart'][$ind]),
                            'date_end' => inputdate_to_mysqldate($_POST['dateend'][$ind]),
                            'price' => $_POST['price'][$ind],
                            'days_count' => $_POST['dayscount'][$ind],
                            'transfer' => $_POST['transfer'][$ind],
                            'transfer_price' => $_POST['transfer_price'][$ind],
                            'remark' => $_POST['remark'][$ind],
                            'voucher_remark' => $_POST['voucher_remark'][$ind],
                            'city_tour' => $_POST['city_tour'][$ind],
                        ));
                    }
                }
            if (isset($_POST['manuel_text']) && is_array($_POST['manuel_text']))
                foreach ($this->input->post('manuel_text') as $ind => $manuel_text)
                {

                    if (isset($_POST['formular_manuel_id'][$ind])) {
                        $manuel = FormularManuel::find_by_id($_POST['formular_manuel_id'][$ind]);

                        $manuel->text = $manuel_text;
                        $manuel->date_start = isset($_POST['manuel_datestart'][$ind]) ? inputdate_to_mysqldate($_POST['manuel_datestart'][$ind]) : '';
                        $manuel->date_end = isset($_POST['manuel_dateend'][$ind]) ? inputdate_to_mysqldate($_POST['manuel_dateend'][$ind]) : '';
                        $manuel->days_count = isset($_POST['manuel_dayscount'][$ind]) ? $_POST['manuel_dayscount'][$ind] : 0;
                        $manuel->price = $_POST['manuel_price'][$ind];
                        $manuel->voucher_remark = $_POST['manuel_voucher_remark'][$ind];

                        $manuel->save();

                        $formular_exist[$manuel->id]['value'] = 1;
                    }
                    else
                    {
                        FormularManuel::create(array(
                            'formular_id' => $formular->id,
                            'status' => 'none',
                            'text' => $manuel_text,
                            'date_start' => isset($_POST['manuel_datestart'][$ind]) ? inputdate_to_mysqldate($_POST['manuel_datestart'][$ind]) : '',
                            'date_end' => isset($_POST['manuel_dateend'][$ind]) ? inputdate_to_mysqldate($_POST['manuel_dateend'][$ind]) : '',
                            'days_count' => isset($_POST['manuel_dayscount'][$ind]) ? $_POST['manuel_dayscount'][$ind] : 0,
                            'price' => $_POST['manuel_price'][$ind],
                            'voucher_remark' => $_POST['manuel_voucher_remark'][$ind]
                        ));
                    }
                }
            foreach ($formular_exist as $item_id => $val)
                if ($val['value'] == 0) {
                    $item = ($val['type'] == "manuel") ? FormularManuel::find_by_id($item_id) : FormularHotel::find_by_id($item_id);
                    $item->delete();
                }

            redirect('reservierung/result/' . $formular->id);
        }


        $this->view_data["formular"] = $formular;

    }

    public function find($mode = "")
    {
        $this->layout_view = '';

        if (empty($mode))
            return "";

        $hotel_code = $this->input->post('hotelcode');
        $room_type = $this->input->post('roomtype');
        $room_capacity = $this->input->post('roomcapacity');
        $hotel_service = $this->input->post('service');
        $date_start = $this->input->post('datestart');
        $date_end = $this->input->post('dateend');

        $result = array();

        switch ($mode)
        {
            case "vnum":
                echo Formular::find_by_v_num($this->input->post('value')) ? 1 : 0;
                break;
            case "name":
                $sql_options = array('conditions' => array('code = ?', $hotel_code),
                    'select' => 'name, stars');
                $hotel = Hotel::first($sql_options);

                if (!$hotel)
                    echo "NO FOUND";
                else
                    echo $hotel->name;

                return TRUE;

            case "room_type":
                $sql_options = array('conditions' => array('hotel_id = ?', Hotel::find_by_code($hotel_code)->id),
                    'select' => 'DISTINCT roomtype_id');
                $room_type = HotelOffer::all($sql_options);

                $result = array();
                foreach ($room_type as $type)
                {
                    $type = RoomType::find_by_id($type->roomtype_id);
                    $result[] = array("id" => $type->id, "value" => $type->value);
                }

                echo json_encode($result);
                break;

            case "room_capacity":
                $sql_options = array('conditions' => array('hotel_id = ? AND roomtype_id = ?', Hotel::find_by_code($hotel_code)->id, $room_type),
                    'select' => 'DISTINCT roomcapacity_id');

                $room_type = HotelOffer::all($sql_options);
                $result = array();
                foreach ($room_type as $type)
                {
                    $type = RoomCapacity::find_by_id($type->roomcapacity_id);
                    $result[] = array("id" => $type->id, "value" => $type->value);
                }

                echo json_encode($result);
                break;

            case "hotel_service":
                $sql_options = array('conditions' => array('hotel_id = ? AND roomtype_id = ? AND roomcapacity_id = ?',
                    Hotel::find_by_code($hotel_code)->id, $room_type, $room_capacity),
                    'select' => 'DISTINCT hotelservice_id');
                $hotel_service = HotelOffer::all($sql_options);
                $result = array();
                foreach ($hotel_service as $type)
                {
                    $type = HotelService::find_by_id($type->hotelservice_id);
                    $result[] = array("id" => $type->id, "value" => $type->value);
                }

                echo json_encode($result);
                break;

            case "price":
                $price = 0;
                $date_start = mysqldate_to_timestamp(inputdate_to_mysqldate($date_start));
                $date_end = mysqldate_to_timestamp(inputdate_to_mysqldate($date_end, TRUE));

                while ($date_start <= $date_end)
                {

                    $sql_options = array('conditions' => array('hotel_id = ? AND roomtype_id = ? AND roomcapacity_id = ? AND hotelservice_id = ?
                        AND date = ?', Hotel::find_by_code($hotel_code)->id, $room_type, $room_capacity, $hotel_service, time_to_mysqldate($date_start)),
                        'select' => 'price');

                    $price_found = HotelOffer::first($sql_options);

                    if (!$price_found) {
                        $price = 0;
                        break;
                    }

                    $price += $price_found->price;
                    $day = getdate($date_start);

                    $date_start = mktime(0, 0, 0, $day['mon'], $day['mday'] + 1, $day['year']);
                }

                echo $price;

                break;
        }
    }


    public function result($id = 0)
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            show_404();
            return false;
        }

        $this->view_data['formular'] = $formular;

        if ($_POST) {

            if (isset($_POST['person_sex']) && is_array($_POST['person_sex']))
                foreach ($_POST['person_sex'] as $ind => $person_sex)
                {
                    if (isset($_POST['person_id'][$ind])) {
                        $person = FormularPerson::find_by_id($_POST['person_id'][$ind]);
                        $person->sex = $_POST['person_sex'][$ind];
                        $person->name = $_POST['person_name'][$ind];
                        $person->surname = $_POST['person_surname'][$ind];

                        $person->save();
                    }
                    else
                        FormularPerson::create(array(
                            "formular_id" => $formular->id,
                            "name" => $_POST['person_name'][$ind],
                            "surname" => $_POST['person_surname'][$ind],
                            "sex" => $_POST['person_sex'][$ind]
                        ));
                }

            $formular->comment = $this->input->post("bigcomment");

            $formular->departure_date = inputdate_to_mysqldate($this->input->post("departure_date"));
            if ($formular->status == "rechnung") {
                $formular->prepayment = $this->input->post("prepayment");
                $formular->prepayment_date = inputdate_to_mysqldate($this->input->post("preprepayment_date"));
                $formular->finalpayment_date = inputdate_to_mysqldate($this->input->post("finalpayment_date"));
            }

            $formular->save();

            $this->write_pdf($formular->id);

            redirect('reservierung/final/' . $formular->id);
        }

    }

    public function status($id)
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            show_404();
            return false;
        }

        if ($_POST) {
            $item = ($this->input->post('item_type') == 'hotel') ? FormularHotel::find_by_id($this->input->post('item_id'))
                : FormularManuel::find_by_id($this->input->post('item_id'));

            FormularStatusLog::create(array(
                'item_type' => $this->input->post('item_type'),
                'item_id' => $this->input->post('item_id'),
                'old_status' => $item->status,
                'new_status' => $this->input->post('status'),
                'comment' => $this->input->post('comment'),
                'user_id' => $this->user->id,
                'datetime' => time_to_mysqldatetime(time()),
            ));

            $item->status = $this->input->post('status');
            $item->save();

            redirect("reservierung/status/" . $formular->id);
        }

        $this->view_data['formular'] = $formular;

    }

    public function final_($id)
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            show_404();
            return false;
        }

        $this->view_data['formular'] = $formular;

        $this->content_view = "reservierung/final";
    }


    public function do_rechnung($id = 0)
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            show_404();
            return false;
        }


        $formular->prepayment = $this->input->post("prepayment");
        $formular->prepayment_date = inputdate_to_mysqldate($this->input->post("preprepayment_date"));
        $formular->finalpayment_date = inputdate_to_mysqldate($this->input->post("finalpayment_date"));
        $formular->departure_date = inputdate_to_mysqldate($this->input->post("departure_date"));


        $next_rnum = Config::find_by_param('next_rnum');
        $formular->status = "rechnung";

        $formular->r_num = "201100/" . $next_rnum->value++;
        $next_rnum->save();

        $formular->rechnung_date = time_to_mysqldate(time());

        $formular->save();
        $this->write_to_pdf($formular->id, 3);
        $this->write_to_pdf($formular->id, 4);

        redirect("reservierung/final/" . $formular->id);
    }

    public function eingangsmitteilung($id = 0)
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            show_404();
            return false;
        }

        $formular->status = "eingangsmitteilung";
        $formular->save();

        $this->write_to_pdf($formular->id, 1);
        $this->write_to_pdf($formular->id, 2);


        redirect("reservierung/final/" . $formular->id);
    }

    public function storeno($id = 0)
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            show_404();
            return false;
        }

        if ($_POST) {
            $formular->status = 'storeno';

            $this->load->library('session');
            $user_session = $this->session->all_userdata();

            FormularStorno::create(array(
                'formular_id' => $formular->id,
                'canceled_by' => $this->input->post('who'),
                'percent' => $this->input->post('percent'),
                'user_id' => $user_session['user_id'],
                'date' => time_to_mysqldate(time()),
            ));

            $formular->save();

            $this->write_to_pdf($formular->id, 6);
            if ($formular->kunde->type == "agenturen")
                $this->write_to_pdf($formular->id, 5);

            redirect("reservierung/final/" . $formular->id);
        }

        $this->view_data['formular'] = $formular;
    }

    public function vouchers($id = "")
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            show_404();
            return false;
        }

        $this->view_data['formular'] = $formular;
        $this->view_data['incoming'] = Kunde::find_all_by_type('incoming');

    }


    public function sendmail($id)
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            echo "Incorrect formular id";
            exit();
        }

        $email = $this->input->post("email");
        $pdf = $this->input->post("type");

        $this->load->library("email");


        $this->email->clear();
        $this->email->from($this->user->email, $this->user->name . " " . $this->user->surname . " <" . $this->user->email . ">");
        $this->email->to($email);

        $subject = '';
        if ($formular->status == 'angebot')
            $subject = 'Angebot: Ihre Reiseanfrage ' . $formular->v_num;
        else if ($formular->status == 'rechnung' || $formular->status == 'freigabe')
            $subject = 'Rechnung: Vielen Dank für Ihre Buchung ' . $formular->r_num;
        else if ($formular->status == 'eingangsmitteilung')
            $subject = 'Rechnung: Vielen Dank für Ihre Buchung ' . $formular->v_num;

        $this->email->subject($subject);

        $text = '';
        if ($formular->status == "angebot")
            $text = Config::find_by_param("emailtext_angebot")->value;
        else if ($formular->status == "eingangsmitteilung")
            $text = Config::find_by_param("emailtext_eingangsmitteilung")->value;
        else if ($formular->status == "rechnung" || $formular->status == "freigabe")
            $text = Config::find_by_param("emailtext_rechnung")->value;

        $filename = 'pdf/' . $this->get_pdf_name($formular->id) . ".pdf";

        $source_file = 'pdf/' . $formular->id . "_" . $pdf . ".pdf";

        @copy($source_file, $filename);

        $this->email->message($text);
        $this->email->attach($filename);

        if ($formular->status == "rechnung" || $formular->status == "freigabe") {

            $this->email->attach('attachments/Reisebedingungen_UniqueWorld.pdf');
            $this->email->attach('attachments/Sicherungsschein_UniqueWorld.pdf');
        }

        if (!$this->email->send())
            echo "error sending";
        else
            echo "ok";

        @unlink($filename);

        exit();
    }


    public function payments($id = 0)
    {
        $formular = Formular::find_by_id($id);

        if (!$formular) {
            show_404();
            return false;
        }

        if ($_POST) {
            FormularPayment::create(array(
                "formular_id" => $id,
                "value" => $this->input->post("payment_value"),
                "datetime" => time_to_mysqldatetime(time()),
                "user_id" => $this->user->id,
            ));

            if ($formular->paid_amount >= $formular->price['brutto']) {
                $formular->status = "freigabe";

                $formular->save();
            }

            redirect("reservierung/final/" . $id);
        }

        $this->view_data['formular'] = $formular;
    }

    public function livesearch($type = "", $str = "")
    {
        $result = array();

        switch ($type)
        {
            case "hotelcode":
                $hotels = Hotel::find('all', array('conditions' => array('code like "%' . $str . '%"')));
                foreach ($hotels as $hotel)
                    $result[] = array(
                        "text" => "<b>" . $hotel->code . "</b> - " . $hotel->name,
                        "value" => $hotel->code,
                        "data" => array("hotelname" => $hotel->name),
                    );
                break;
            case "hotelname":
                $hotels = Hotel::find('all', array('conditions' => array('name like "%' . $str . '%"')));
                foreach ($hotels as $hotel)
                    $result[] = array(
                        "text" => "<b>" . $hotel->code . "</b> - " . $hotel->name,
                        "value" => $hotel->name,
                        "data" => array("hotelcode" => $hotel->code),
                    );
                break;

            case "vnum":
                $formulars = Formular::find('all', array('conditions' => array('v_num like "%' . $str . '%"')));
                foreach ($formulars as $formular)
                    $result[] = array(
                        "text" => $formular->status . ": <b>" . $formular->v_num . "</b>",
                        "value" => $formular->v_num
                    );
                break;

            case "rnum":
                $formulars = Formular::find('all', array('conditions' => array('r_num like "%' . $str . '%"')));
                foreach ($formulars as $formular)
                    $result[] = array(
                        "text" => $formular->status . ": <b>" . $formular->r_num . "</b> (" . $formular->v_num . ")",
                        "value" => $formular->r_num
                    );
                break;

            case "kundename":
                $persons = FormularPerson::find('all', array(
                    'conditions' => array('name like "%' . $str . '%" OR surname like "%' . $str . '%"')));

                foreach ($persons as $person)
                {
                    $formular = Formular::find_by_id($person->formular_id);
                    $result[] = array(
                        "text" => $formular->status . ": " . $formular->v_num . " <b>" . $person->name . " " . $person->surname . "</b>",
                        "value" => $person->name . " " . $person->surname,
                        "data" => array("formular_id" => $formular->id),
                    );
                }
                break;
        }

        echo json_encode($result);
        exit();
    }

    public function open()
    {
        if ($_POST) {
            if (isset($_POST['view-vnum']))
                $formular = Formular::find_by_v_num($_POST['v_num']);
            elseif (isset($_POST['view-rnum']))
                $formular = Formular::find_by_r_num($_POST['r_num']);
            elseif (isset($_POST['view-kundename']))
            {
                $formular_id = $this->input->post("formular_id");
                if ($formular_id)
                    $formular = Formular::find_by_id($_POST['formular_id']);
            }

            redirect("reservierung/final/" . $formular->id);
        }
    }
}
