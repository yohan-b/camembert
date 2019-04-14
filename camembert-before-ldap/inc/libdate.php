
<?php
function get_month($datedeco) {
                $deco = explode("-", $datedeco);
                return (int)($deco[1]);
}

function get_year($datedeco) {
                $deco = explode("-", $datedeco);
                return (int)($deco[0]);
}

function LowestDate($date1, $date2) {
        $month1 = get_month($date1);
        $year1 = get_year($date1);
        $month2 = get_month($date2);
        $year2 = get_year($date2);
        if ($year1 < $year2)
                return $date1;
        elseif ($year2 < $year1)
                return $date2;
        elseif ($month1 <= $month2)
                return $date1;
        else 
                return $date2;
}

function CompareDate($date1, $date2) {
        if ((get_year($date1) == get_year($date2)) && (get_month($date1) == get_month($date2)))
                return 0;
        elseif (LowestDate($date1, $date2) == $date1)
                return 1;
        else
                return 2;
}               

// retourne la date de début de l'année universitaire
function StartDate() {
        if(date("n") < 9)
                return (date("Y") - 1)."-09";
        else
                return date("Y")."-09";
}
?>
