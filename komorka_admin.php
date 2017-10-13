<?php
session_start();

if (!isset($_SESSION['zalogowany'])) {

    header('Location: index.php');
    exit();
}

require_once "connect.php";

$polaczenie = @new mysqli($host, $db_user, $db_password, $db_name);
$polaczenie->set_charset("utf8");
if ($polaczenie->connect_errno != 0) {
    echo "Error: " . $polaczenie->connect_errno;
}
$date = $_GET['date'];
$matrix = [];
foreach (range(12, 16) as $hour) {
    $matrix[$hour] = [
        '1' => [
            'hour' => $hour,
            'column' => "1",
        ],
        '2' => [
            'hour' => $hour,
            'column' => "2",
        ],
    ];
}
try {
    $pdo = new PDO('mysql:host=localhost;dbname=wozek;charset=utf8', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION, PDO::FETCH_ASSOC);

    $stmt = $pdo->query('SELECT `nazwa`, `id`, `email` FROM `uzytkownicy`');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $all_users_list[] = $row;
    }
    $stmt->closeCursor();
} catch (PDOException $e) {
    echo 'Połączenie nie mogło zostać utworzone: ' . $e->getMessage();
}
if ($rezultat = $polaczenie->query(
    sprintf
    ("SELECT *, e.id AS event_id FROM events AS e LEFT JOIN uzytkownicy AS u ON e.user_id = u.id WHERE `date`='%s'",
        mysqli_real_escape_string($polaczenie, $date)))) {
    $events = $rezultat->fetch_all(MYSQLI_ASSOC);
    foreach ($events as $event) {
        if ($event['confirmed'] == 1 && ($event['nazwa'])) {
            list($name, $surname) = explode(" ", $event['nazwa'], 2);
            $event['shortname'] = $name[0] . ". " . $surname;
            $event['button_class'] = "switch";
            $event['button_status'] = "disabled";
            $event['remove_icon'] = "<i class=\"fa fa-minus-square\"></i>";
        }
        if ($event['confirmed'] == 0 && ($event['nazwa'])) {
            list($name, $surname) = explode(" ", $event['nazwa'], 2);
            $event['shortname'] = $name[0] . ". " . $surname;
            $event['button_class'] = "waiting";
            $event['button_status'] = "disabled";
            $event['add_icon'] = "<i class=\"fa fa-plus-square\"></i>";
            $event['remove_icon'] = "<i class=\"fa fa-minus-square\"></i>";
        }
        $matrix[$event['hour']][$event['column']] = $event;
    }
    //print_r($matrix); die();
}


?>
<html>
<head>

    <style>
        .scrollable-menu {
            height: auto;
            max-height: 200px;
            width: 190px;
            overflow-x: hidden;
        }

        .button-icon {
            color: #313e39;
            cursor: pointer;
            width: 16px;
            height: 14px;
        }
    </style>
</head>
<body>
<div class="row">
    <div class="col-sm-12">
        <?php foreach ($matrix as $column => $cells): ?>
            <div class="row guttersmall">
                <div class="col-sm-2">
                    <div class="hours">
                        <?php echo $column ?>
                    </div>
                </div>
                <?php foreach ($cells as $event): ?>
                    <div class="col-sm-4">
                        <div class="dropdown">
                            <button class="<?php echo $event['button_class'] ?? "dropdown-toggle switch" ?>
                            <?php echo $event['button_status'] ?? 'data-toggle="dropdown"' ?>"
                                    data-toggle="dropdown"
                                    data-hour="<?php echo $event['hour'] ?>"
                                    data-column="<?php echo $event['column'] ?>"
                                    data-date="<?php echo $date ?>">
                                <?php echo $event['shortname']; ?>
                            </button>
                            <div class="dropdown-menu scrollable-menu" role="menu" >
                                <?php foreach ($all_users_list as $key): ?>
                                    <a class="dropdown-item" href="#" data-toggle="<?php echo $key['nazwa'] ?>"
                                       onclick="addFromList(this)"><?php echo $key['nazwa'] ?></a>
                                <?php endforeach ?>
                            </div>
                            <button class="switch" style="display: none">coto
                            </button>
                        </div>
                    </div>
                    <div class="col-sm-1" style="margin-left: -10px">
                        <button type="submit" class="btn btn-link btn-sm  float-left remove-button button-icon"
                                data-id="<?php echo $event['event_id'] ?>">
                            <?php echo $event['remove_icon'] ?>
                        </button>
                        <button type="submit" class="btn btn-link btn-sm  float-left add-button button-icon"
                                data-id="<?php echo $event['event_id'] ?>">
                            <?php echo $event['add_icon'] ?>
                        </button>
                    </div>
                <?php endforeach ?>

            </div>
        <?php endforeach ?>
    </div>
</div>
<div id="footer" class="modal-footer">
    <button id="send" type="button" class="btn float-right">ZAPISZ</button>
</div>
<div id="checker">

</div>

<script>
    function addFromList() {
        $( '.switch' ).toggle();
      //  var
     //   var add_new = [];
     //        add_new.push({
     //            date: $('.').data("date"),
      //           hour: $element.data("hour"),
     //            column: $element.data("column")
    //         });$(this).data()

    }

    $('.remove-button').click(function () {

        var data = $(this).data();
        console.log(data);
        $.ajax({
            type: "POST",
            url: '/remRequest.php',
            data: data,
            success: function () {
                $.ajax({
                    type: "GET",
                    url: '/komorka_admin.php',
                    data: {
                        date: "<?php echo $date ?>"
                    },
                    success: function (response) {
                        $('#exampleModal .modal-body').html(response);
                    }
                });
            }
        });
    });
    $('.add-button').click(function () {

        var data = $(this).data();
        console.log(data);
        $.ajax({
            type: "POST",
            url: '/addRequest.php',
            data: data,
            success: function () {
                $.ajax({
                    type: "GET",
                    url: '/komorka_admin.php',
                    data: {
                        date: "<?php echo $date ?>"
                    },
                    success: function (response) {
                        $('#exampleModal .modal-body').html(response);
                    }
                });
            }
        });
    })
</script>

</body>
</html>