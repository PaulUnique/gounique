<div class="header">
    <div class="info">
        <?=$formular->kunde->plain_text?>
    </div>
</div>
<div class="content">
    <h1>BUCHUNGSBESTÄTIGUNG / RECHNUNG</h1>

    <table class="top-block">
        <tr>
            <td class="left-paramname">Vorgangsnummer:</td>
            <td class="left-paramvalue"><?=$formular->v_num?></td>
            <td class="right-paramname">Datum:</td>
            <td class="right-paramvalue"><?=$formular->rechnung_date->format("d. M. Y")?></td>
        </tr>
        <tr>
            <td class="left-paramname">Rechnungsnummer:</td>
            <td class="left-paramvalue"><?=$formular->r_num?></td>
            <td class="right-paramname">Sachbearbeiter:</td>
            <td class="right-paramvalue"><?=$formular->sachbearbeiter?></td>
        </tr>
        <tr>
            <td class="left-paramname">Kundennummer:</td>
            <td class="left-paramvalue"><?=$formular->kunde->k_num?></td>
            <td class="right-paramname" colspan="2"></td>
        </tr>
    </table>

    <div class="main-block">

        <div class="block first">
            <h3>Reiseteilnehmer:</h3>
            <table class="reiseteilnehmer-table">
                <? foreach ($formular->persons as $ind => $person): ?>
                <tr>
                    <td class="num"><?=($ind + 1)?></td>
                    <td class="sex"><?=$person->plain_sex?></td>
                    <td class="person-name"><?=$person->name . "/" . $person->surname?></td>
                </tr>
                <? endforeach; ?>
            </table>
        </div>

        <div class="block">
            <h3>Reisezeitraum:</h3>
            <table class="reisezeitraum-table">
                <tr>
                    <td><?=$formular->departure_date->format('d. M. y')?></td>
                    <td class="center">bis</td>
                    <td><?=($formular->arrival_date) ? $formular->arrival_date->format('d. M. y') : ''?></td>
                </tr>
            </table>
        </div>

        <div class="block">
            <h3>Leistung:</h3>
            <table class="liestung-table">
                <? foreach ($formular->hotels_and_manuels as $ind => $item): ?>
                <tr>
                    <td class="num"><?=($ind + 1)?></td>
                    <td class="text"><?=$item->pdf_text?></td>
                </tr>
                <? endforeach; ?>
            </table>
        </div>

        <?if ($formular->flight_text != ""): ?>
        <div class="block">
            <h3>Flugplan:</h3>

            <div class="flight-plan">
                <pre><?=$formular->flight_text?></pre>
            </div>
        </div>
        <? endif; ?>
    </div>

    <div class="anzahlung-block">
        <? if ($formular->status == "rechnung"): ?>
        <? if ($formular->finalpayment_date): ?>
            <p>Anzahlung sofort nach Erhalt der Rechnung: <?=$formular->price['anzahlung_value']?> &euro;</p>
            <p>Restzahlung f&auml;llig am: <?=$formular->finalpayment_date->format('d-M-y')?>
                &nbsp;&nbsp;<?=$formular->price['restzahlung']?> &euro;</p>
            <? else: ?>
            <p>Zahlung sofort nach Erhalt de Rechnung</p>
            <? endif; ?>
        <? endif; ?>

        <div class="bank-block">
            Bitte überweisen Sie den Rechnungsbetrag auf unser Geschäftskonto:<br>
            <div class="param"><b>Commerzbank AG</b></div>
            <div class="param"><b>Kto.: 420 131 500</b></div>
            <div class="param"><b>BLZ: 200 400 00</b></div>
            <div class="param"><b>Zweck: <?=$formular->r_num?></b> (Bitte unbedingt angeben!)</div>
        </div>
    </div>

    <div class="price-table">
        <table>
            <tr>
                <td class="paramname">Preis Brutto/p.Person</td>
                <td class="paramvalue"><?=$formular->price['person']?> &euro;</td>
            </tr>
            <tr class="bold underline">
                <td class="paramname">Gesamtpreis</td>
                <td class="paramvalue"><?=$formular->price['brutto']?> &euro;</td>
            </tr>
            <? if ($formular->kunde->type == 'agenturen'): ?>
            <tr class="green">
                <td class="paramname">Provision <?=$formular->provision?>%</td>
                <td class="paramvalue"><?=$formular->price['provision']?> &euro;</td>
            </tr>
            <tr class="green">
                <td class="paramname">MWST auf Prov 19%</td>
                <td class="paramvalue"><?=$formular->price['mwst']?> &euro;</td>
            </tr>
            <tr class="green">
                <td class="paramname">Total Provision:</td>
                <td class="paramvalue"><?=$formular->price['total_provision']?> &euro;</td>
            </tr>
            <tr class="empty">
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr class="bold">
                <td class="paramname">Endpreise Netto</td>
                <td class="paramvalue"><?=$formular->price['netto']?> &euro;</td>
            </tr>
            <? endif?>
        </table>
    </div>
</div>