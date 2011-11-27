<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <title>Dashboard | Dashboard Admin</title>

    <base href="<?=base_url()?>" />

    <link rel="stylesheet" href="css/reset.css" type="text/css" media="screen" title="no title"/>
    <link rel="stylesheet" href="css/text.css" type="text/css" media="screen" title="no title"/>
    <link rel="stylesheet" href="css/form.css" type="text/css" media="screen" title="no title"/>
    <link rel="stylesheet" href="css/buttons.css" type="text/css" media="screen" title="no title"/>
    <link rel="stylesheet" href="css/grid.css" type="text/css" media="screen" title="no title"/>
    <link rel="stylesheet" href="css/layout.css" type="text/css" media="screen" title="no title"/>

    <link rel="stylesheet" href="css/main.css" type="text/css"/>
    <link rel="stylesheet" href="css/print.css" type="text/css" media="print"/>
    <script src="js/jquery/jquery-1.6.4.min.js"></script>
    <script src="js/jquery/jquery-ui-1.8.16.custom.min.js"></script>
    <script src="js/global.js"></script>
    <script src="js/misc/excanvas.min.js"></script>
    <script src="js/jquery/facebox.js"></script>
    <script src="js/jquery/jquery.visualize.js"></script>
    <script src="js/jquery/jquery.dataTables.min.js"></script>
    <script src="js/jquery/jquery.tablesorter.min.js"></script>
    <script src="js/jquery/jquery.uniform.min.js"></script>
    <script src="js/jquery/jquery.placeholder.min.js"></script>
    <script src="js/widgets.js"></script>

    <? if(isset($JS_files))
        foreach($JS_files as $js): ?>
        <script src="<?=$js?>"></script>
    <? endforeach; ?>
 
    <link rel="stylesheet" href="css/cupertino/jquery-ui-1.8.16.custom.css" type="text/css" media="screen"
          title="no title"/>
    <link rel="stylesheet" href="css/plugin/jquery.visualize.css" type="text/css" media="screen" title="no title"/>
    <link rel="stylesheet" href="css/plugin/facebox.css" type="text/css" media="screen" title="no title"/>
    <link rel="stylesheet" href="css/plugin/uniform.default.css" type="text/css" media="screen" title="no title"/>
    <link rel="stylesheet" href="css/plugin/dataTables.css" type="text/css" media="screen" title="no title"/>
    <link rel="stylesheet" href="css/custom.css" type="text/css" media="screen" title="no title">
</head>

<div id="wrapper">
    <div id="top">
        <div class="content_pad">
            <ul class="right">
                <li><a href="javascript:;" class="top_icon"><span class="ui-icon ui-icon-person"></span>Eingeloggt als <?=$user->name." ".$user->surname?></a></li>
                <!--	<li><a href="javascript:;" class="new_messages top_alert">1 New Message</a></li>
               <li><a href="./pages/settings.html">Settings</a></li>-->
                <li><a href="logout">Logout</a></li>
            </ul>
        </div>
    </div>

    <div id="header">
        <div class="content_pad">
            <h1><a href="dashboard">Dashboard Admin</a></h1>
            <ul id="nav">
                <li class="nav_icon <?if($current_page == 'formular_create') echo 'nav_current' ?>">
                    <a href="formular/create">New formular</a>
                </li>
                <li class="nav_icon <?if($current_page == 'formular_open') echo 'nav_current' ?>">
                    <a href="formular/open">Open formular</a>
                </li>
            </ul>
        </div>
    </div>


    <div id="masthead">
        <div class="content_pad">
            <h1 class="no_breadcrumbs">
                <?
                    if($current_page == 'dashboard_index')
                        echo "Dashboard";
                    elseif($current_page == 'formular_create')
                        echo "Reiseangebot Formular";
                    elseif($current_page == 'formular_open')
                        echo "Open formular";
                    elseif($current_page == "agency_view")
                        echo "Agency";
                    elseif($current_page == "formular_view")
                    {
                        echo ($formular->stage == 1 ? "Angebot" : "Rechnung")." Formular: ";
                        echo $agency->type == 'person' ? $agency->name ." ". $agency->surname : $agency->name;
                        if($formular->stage == 2)
                            echo '<span class="right-float">Rechnungnummer: '.$formular->r_num.'</span>';
                    }
                ?>
            </h1>
        </div>
    </div>

    <div class="content xgrid">
        <?=$main_content?>
    </div>


    <div id="footer">
        <div class="content_pad">
            <p>&copy; 2010-11 Copyright <a href="#">goUnique</a>. Powered by <a href="#">UniqueWorld.de</a>.</p>
        </div>
    </div>

    </body>
</html>