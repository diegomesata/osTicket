<?php
require('staff.inc.php');

//require_once INCLUDE_DIR . 'class.report.php';

/*
if ($_POST['export']) {
    $report = new OverviewReport($_POST['start'], $_POST['period']);
    switch (true) {
    case ($data = $report->getTabularData($_POST['export'])):
        $ts = strftime('%Y%m%d');
        $group = Format::slugify($_POST['export']);
        $delimiter = ',';
        if (class_exists('NumberFormatter')) {
            $nf = NumberFormatter::create(Internationalization::getCurrentLocale(),
                NumberFormatter::DECIMAL);
            $s = $nf->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
            if ($s == ',')
                $delimiter = ';';
        }

        Http::download("stats-$group-$ts.csv", 'text/csv');
        $output = fopen('php://output', 'w');
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $data['columns'], $delimiter);
        foreach ($data['data'] as $row)
            fputcsv($output, $row, $delimiter);
        exit;
    }
}

$nav->setTabActive('dashboard');*/

$from = $_POST['from'];
$to =  $_POST['to'];


date_default_timezone_set('America/Bogota');
//echo date_default_timezone_get();

if(!$from)
    $from = date("Y-m-d", strtotime("-1 months"));
    
if(!$to)
    $to = date("Y-m-d", strtotime('tomorrow'));
    
$sql = "SELECT   
  CONCAT(ost_staff.firstname, ' ', ost_staff.lastname)  AS Staff,    
  (CASE ost_ticket_status.name LIKE 'Resolved' WHEN TRUE THEN 'Closed' ELSE ost_ticket_status.name END) AS STATUS,
  COUNT(*) AS cnt, SUM(  CASE (CASE closed IS NULL WHEN TRUE THEN NOW() ELSE closed END) > est_duedate WHEN TRUE THEN 1 ELSE 0 END) AS overdue, AVG( TIME_TO_SEC(TIMEDIFF(closed, CASE reopened IS NULL WHEN TRUE THEN ost_ticket.created ELSE ost_ticket.reopened END))*9/86400) AS resptime
  , SUM(CASE reopened IS NULL WHEN TRUE THEN 0 ELSE 1 END) AS reopened
FROM ost_ticket
  JOIN ost_staff ON ost_staff.staff_id = ost_ticket.staff_id
  JOIN ost_department ON ost_ticket.dept_id = ost_department.id  
  JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id
WHERE ost_ticket.created >= '" . $from . "' and ost_ticket.created <= '" . $to
  . "' GROUP BY ost_staff.username, (CASE ost_ticket_status.name LIKE 'Resolved' WHEN TRUE THEN 'Closed' ELSE ost_ticket_status.name END)";
  
  $result = db_query($sql);
  
  $results_array = array();
  
  while ($row = $result->fetch_assoc()) {
    $results_array[] = $row;
  }
  
  $sql2 = "SELECT   
  ost_department.name AS Staff,    
  (CASE ost_ticket_status.name LIKE 'Resolved' WHEN TRUE THEN 'Closed' ELSE ost_ticket_status.name END) AS STATUS,
  COUNT(*) AS cnt, SUM(  CASE (CASE closed IS NULL WHEN TRUE THEN NOW() ELSE closed END) > est_duedate WHEN TRUE THEN 1 ELSE 0 END) AS overdue, AVG( TIME_TO_SEC(TIMEDIFF(closed, CASE reopened IS NULL WHEN TRUE THEN ost_ticket.created ELSE ost_ticket.reopened END))*9/86400) AS resptime
  , SUM(CASE reopened IS NULL WHEN TRUE THEN 0 ELSE 1 END) AS reopened
FROM ost_ticket
  JOIN ost_staff ON ost_staff.staff_id = ost_ticket.staff_id
  JOIN ost_department ON ost_ticket.dept_id = ost_department.id  
  JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id
WHERE ost_ticket.created >= '" . $from . "' and ost_ticket.created <= '" . $to
  . "' GROUP BY ost_department.name, (CASE ost_ticket_status.name LIKE 'Resolved' WHEN TRUE THEN 'Closed' ELSE ost_ticket_status.name END)";


$result = db_query($sql2);
  
  $deps_array = array();
  
  while ($row = $result->fetch_assoc()) {
    $deps_array[] = $row;
  }
  
  //Por tarea
  
  $sql3 = "SELECT   
  CONCAT(ost_staff.firstname, ' ', ost_staff.lastname) AS Staff,    
  CASE closed IS NULL WHEN TRUE THEN 'Open' ELSE 'Closed' END AS STATUS,
  COUNT(*) AS cnt, SUM(  CASE (CASE closed IS NULL WHEN TRUE THEN NOW() ELSE closed END) > duedate WHEN TRUE THEN 1 ELSE 0 END) AS overdue, AVG( TIME_TO_SEC(TIMEDIFF(closed, ost_task.created )*9/86400)) AS resptime  
FROM ost_task
  JOIN ost_staff ON ost_staff.staff_id = ost_task.staff_id
  JOIN ost_department ON ost_task.dept_id = ost_department.id    
WHERE ost_task.created >= '" . $from . "' AND ost_task.created <= '" . $to
. "' GROUP BY ost_staff.username,CASE closed IS NULL WHEN TRUE THEN 'Open' ELSE 'Closed' END";


$result = db_query($sql3);
  
  $tasks_array = array();
  
  while ($row = $result->fetch_assoc()) {
    $tasks_array[] = $row;
  }

//Por organización

$sql4 = "SELECT 
  ost_organization.name AS Staff,
  (CASE ost_ticket_status.name LIKE 'Resolved' WHEN TRUE THEN 'Closed' ELSE ost_ticket_status.name END) AS STATUS,
  COUNT(DISTINCT ost_ticket.ticket_id) AS cnt,
  SUM(CASE reopened IS NULL WHEN TRUE THEN 0 ELSE 1 END) AS reopened,
  SUM(  CASE (CASE closed IS NULL WHEN TRUE THEN NOW() ELSE closed END) > est_duedate WHEN TRUE THEN 1 ELSE 0 END) AS overdue, 
  AVG( TIME_TO_SEC(TIMEDIFF(closed, CASE reopened IS NULL WHEN TRUE THEN ost_ticket.created ELSE ost_ticket.reopened END))*9/86400) AS resptime  
FROM ost_ticket
  JOIN ost_staff ON ost_staff.staff_id = ost_ticket.staff_id
  JOIN ost_department ON ost_ticket.dept_id = ost_department.id
  JOIN ost_user_email ON ost_ticket.user_id = ost_user_email.user_id
  JOIN ost_user ON ost_user_email.id = ost_user.id
  JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id
  JOIN ost_organization ON ost_user.org_id = ost_organization.id
  WHERE ost_ticket.created >= '" . $from . "' and ost_ticket.created <= '" . $to
   . "' GROUP BY ost_organization.name, (CASE ost_ticket_status.name LIKE 'Resolved' WHEN TRUE THEN 'Closed' ELSE ost_ticket_status.name END)";


$result = db_query($sql4);
  
  $orgs_array = array();
  
  while ($row = $result->fetch_assoc()) {
    $orgs_array[] = $row;
  }


  $sql5 = "SELECT
  CONCAT(ost_staff.firstname, ' ', ost_staff.lastname)  AS Staff,  
      `ost_thread_entry`.`quality_item_id`
      , `ost_thread_entry`.`staff_id`
      , COUNT(`ost_thread_entry`.`quality_item_id`) AS count
      , `ost_quality_item`.`item`
  FROM
      `dissertu_tiquetes`.`ost_thread`
      INNER JOIN `dissertu_tiquetes`.`ost_thread_entry` 
          ON (`ost_thread`.`id` = `ost_thread_entry`.`thread_id`)
      INNER JOIN `dissertu_tiquetes`.`ost_staff` 
          ON (`ost_thread_entry`.`staff_id` = `ost_staff`.`staff_id`)
      INNER JOIN `dissertu_tiquetes`.`ost_quality_item` 
          ON (`ost_thread_entry`.`quality_item_id` = `ost_quality_item`.`quality_item_id`)
      INNER JOIN `dissertu_tiquetes`.`ost_ticket` 
          ON (`ost_ticket`.`ticket_id` = `ost_thread`.`object_id`)
  WHERE (`ost_thread_entry`.`quality_item_id` IS NOT  NULL)
  AND ost_thread_entry.created >= '" . $from . "' and ost_thread_entry.created <= '" . $to
 . "' GROUP BY `ost_thread_entry`.`quality_item_id`, `ost_thread_entry`.`staff_id`, `ost_thread_entry`.`quality_item_id`";

 //echo $sql5;

 $result = db_query($sql5);
  
  $q_array = array();
  
  while ($row = $result->fetch_assoc()) {
    $q_array[] = $row;
  }

  $sql6 = "SELECT * FROM ost_quality_item ORDER BY quality_item_id";

  $result = db_query($sql6);
  
  $qi_array = array();
  
  while ($row = $result->fetch_assoc()) {
    $qi_array[] = $row;
  }

//$outp = $result->fetch_all(MYSQLI_ASSOC);

/*$post_data = array(
  'item' => array(
    'item_type_id' => $item_type,
    'string_key' => $string_key,
    'string_value' => $string_value,
    'string_extra' => $string_extra,
    'is_public' => $public,
   'is_public_for_contacts' => $public_contacts
  )
);*/

//echo json_encode($results_array);

//echo "hola mundo";

 //$sql ='SELECT ticket.number as number FROM '.TICKET_TABLE.' ticket '
      //       .' WHERE ticket.ticket_id<=100';
    //$result = db_query($sql);
    //$row = db_fetch_array($result);
      //  echo $row['number'];
        
        //$outp = $result->fetch_all(MYSQLI_ASSOC);

//echo json_encode($outp);



/*
$ost->addExtraHeader('<meta name="tip-namespace" content="dashboard.dashboard" />',
    "$('#content').data('tipNamespace', " + json_encode($outp) + "");");*/


require(STAFFINC_DIR.'header.inc.php');
//require_once(STAFFINC_DIR.'dashboard.inc.php');

/*
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.7.2/angular.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.9.1/underscore-min.js"></script>
<script src="/tiquetes/scp/js/stats.js?11"></script>*/
?>


<div ng-controller="myCtrl">
    
    
<input type="hidden" id="jsonres" value='<?php echo json_encode($results_array); ?>' />
<input type="hidden" id="deps" value='<?php echo json_encode($deps_array); ?>' />
<input type="hidden" id="tasks" value='<?php echo json_encode($tasks_array); ?>' />
<input type="hidden" id="orgs" value='<?php echo json_encode($orgs_array); ?>' />
<input type="hidden" id="quality" value='<?php echo json_encode($q_array); ?>' />
<input type="hidden" id="qualityitems" value='<?php echo json_encode($qi_array); ?>' />

    <form method="post" action="stats.php">
        Desde:
        <input type="date" name="from" value="<?php echo $from ?>" />
        Hasta: <input type="date" name="to" value="<?php echo $to ?>" />
        <?php csrf_token(); ?>
        <input type="submit" value="Cargar"/>
    </form>

<br>

<h2>Tiquetes</h2>

<h3>Estadísticas por agente</h3>

    <table class="dashboard-stats table" ng-cloak>
        <tbody>
            <tr>
                <th>Agente</th>
                <th>Asignados</th>
                <th>Abiertos</th>
                <th>Abiertos<br>Atrasados</th>
                <th>Cerrados</th>
                <th>Cerrados<br>Atrasados</th>
                <th>Reabiertos</th>
                <th>Promedio <br>Respuesta (horas)</th>
            </tr>
        </tbody>
        <tbody>
            <tr ng-repeat="i in data">
                <td>{{i.Staff}}</td>
                <td>{{i.Assigned}}</td>
                <td>{{i.Open}}</td>
                <td>{{i.OpenOverdue}}</td>
                <td>{{i.Closed}}</td>
                <td>{{i.ClosedOverdue}}</td>
                <td>{{i.Reopened}}</td>
                <td>{{i.RespTime | number: 2}}</td>
            </tr>
        </tbody>
        
    </table>
    
    <br>
    <h3>Estadísticas por departamento</h3>

    <table class="dashboard-stats table" ng-cloak>
        <tbody>
            <tr>
                <th>Departamento</th>
                <th>Asignados</th>
                <th>Abiertos</th>
                <th>Abiertos<br>Atrasados</th>
                <th>Cerrados</th>
                <th>Cerrados<br>Atrasados</th>
                <th>Reabiertos</th>
                <th>Promedio <br>Respuesta (horas)</th>
            </tr>
        </tbody>
        <tbody>
            <tr ng-repeat="i in deps">
                <td>{{i.Staff}}</td>
                <td>{{i.Assigned}}</td>
                <td>{{i.Open}}</td>
                <td>{{i.OpenOverdue}}</td>
                <td>{{i.Closed}}</td>
                <td>{{i.ClosedOverdue}}</td>
                <td>{{i.Reopened}}</td>
                <td>{{i.RespTime | number: 2}}</td>
            </tr>
        </tbody>
        
    </table>
    
    <br>
    <h3>Estadísticas por organización</h3>

    <table class="dashboard-stats table" ng-cloak>
        <tbody>
            <tr>
                <th>Organización</th>
                <th>Asignados</th>
                <th>Abiertos</th>
                <th>Abiertos<br>Atrasados</th>
                <th>Cerrados</th>
                <th>Cerrados<br>Atrasados</th>
                <th>Reabiertos</th>
                <th>Promedio <br>Respuesta (horas)</th>
            </tr>
        </tbody>
        <tbody>
            <tr ng-repeat="i in orgs">
                <td>{{i.Staff}}</td>
                <td>{{i.Assigned}}</td>
                <td>{{i.Open}}</td>
                <td>{{i.OpenOverdue}}</td>
                <td>{{i.Closed}}</td>
                <td>{{i.ClosedOverdue}}</td>
                <td>{{i.Reopened}}</td>
                <td>{{i.RespTime | number: 2}}</td>
            </tr>
        </tbody>
        
    </table>

    <br>
    <h3>Calidad</h3>
    <table class="dashboard-stats table" ng-cloak>
        <tbody>
            <tr>
                <th><span style="font-size:xx-small">Agente</span></th>
                <th ng-repeat="q in qHeader"><span style="font-size:xx-small">{{q.short}}</span></th>
            </tr>
        </tbody>
        <tbody>
            <tr ng-repeat="q in quality">
                <td>{{q.Staff}}</td>
                <td ng-repeat="qCount in q.items">{{qCount.valor}}</td>
            </tr>
        </tbody>        
    </table>   
    
    <br>
    <br>
    <h2>Tareas</h2>
    
    <table class="dashboard-stats table" ng-cloak>
        <tbody>
            <tr>
                <th>Agente</th>
                <th>Asignados</th>
                <th>Abiertos</th>
                <th>Abiertos<br>Atrasados</th>
                <th>Cerrados</th>
                <th>Cerrados<br>Atrasados</th>
                <th>Promedio <br>Respuesta (horas)</th>
            </tr>
        </tbody>
        <tbody>
            <tr ng-repeat="i in tasks">
                <td>{{i.Staff}}</td>
                <td>{{i.Assigned}}</td>
                <td>{{i.Open}}</td>
                <td>{{i.OpenOverdue}}</td>
                <td>{{i.Closed}}</td>
                <td>{{i.ClosedOverdue}}</td>
                <td>{{i.RespTime | number: 2}}</td>
            </tr>
        </tbody>
        
    </table>
    
</div>

<?php
include(STAFFINC_DIR.'footer.inc.php');
?>