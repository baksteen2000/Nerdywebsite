<!-- dit bestand bevat alle code die verbinding maakt met de database -->
<?php

function connectToDatabase() {
    $Connection = null;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Set MySQLi to throw exceptions
    try {
        $Connection = mysqli_connect("10.0.1.2:3306", "webshopgebruiker", "gebruiker", "nerdygadgets");
        mysqli_set_charset($Connection, 'latin1');
        $DatabaseAvailable = true;
    } catch (mysqli_sql_exception $e) {
        $DatabaseAvailable = false;
        try {
            $Connection = mysqli_connect("10.0.1.3:3306", "webshopgebruiker", "gebruiker", "nerdygadgets");
            $DatabaseAvailable = true;
        } catch (mysqli_sql_exception $e) {
            $DatabaseAvailable = false;
        }
    }
    if (!$DatabaseAvailable) {
        ?><h2>Website wordt op dit moment onderhouden.</h2><?php
        die();
    }

    return $Connection;
}
// Deze functie maakt verbinding met de database als de webshopgebruiker user

function connectToDatabase_admin() {
    $Connection = null;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Set MySQLi to throw exceptions
    try {
        $Connection = mysqli_connect("10.0.1.2:3306", "client", "Banaan!", "nerdygadgets");
        mysqli_set_charset($Connection, 'latin1');
        $DatabaseAvailable = true;
    } catch (mysqli_sql_exception $e) {
        $DatabaseAvailable = false;
        try {
            $Connection = mysqli_connect("10.0.1.3:3306", "client", "Banaan!", "nerdygadgets");
            $DatabaseAvailable = true;
        } catch (mysqli_sql_exception $e) {
            $DatabaseAvailable = false;
        }
    }
    if (!$DatabaseAvailable) {
        ?><h2>Website wordt op dit moment onderhouden.</h2><?php
        die();
    }

    return $Connection;
}
// Deze functie maakt verbinding met de database als de ROOT user

function getHeaderStockGroups($databaseConnection) {
    $Query = "
                SELECT StockGroupID, StockGroupName, ImagePath
                FROM stockgroups_gebruiker 
                WHERE StockGroupID IN (
                                        SELECT StockGroupID 
                                        FROM stockitemstockgroups_gebruiker
                                        ) AND ImagePath IS NOT NULL
                ORDER BY StockGroupID ASC";
    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_execute($Statement);
    $HeaderStockGroups = mysqli_stmt_get_result($Statement);
    return $HeaderStockGroups;
}
// Deze functie haalt de verschillende categorien en plaatjes hiervan uit de database

function getStockGroups($databaseConnection) {
    $Query = "
            SELECT StockGroupID, StockGroupName, ImagePath
            FROM stockgroups_gebruiker 
            WHERE StockGroupID IN (
                                    SELECT StockGroupID 
                                    FROM stockitemstockgroups_gebruiker
                                    ) AND ImagePath IS NOT NULL
            ORDER BY StockGroupID ASC";
    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_execute($Statement);
    $Result = mysqli_stmt_get_result($Statement);
    $StockGroups = mysqli_fetch_all($Result, MYSQLI_ASSOC);
    return $StockGroups;
}
// Deze functie haalt de verschillende categorien en plaatjes hiervan uit de database

function getStockItem($id, $databaseConnection) {
    $Result = null;

    $Query = " 
           SELECT SI.StockItemID, 
            (RecommendedRetailPrice*(1+(TaxRate/100))) AS SellPrice, 
            StockItemName,
            RecommendedRetailPrice,
            TaxRate,
            CONCAT('Voorraad: ',QuantityOnHand)AS QuantityOnHand,
            SearchDetails, 
            (CASE WHEN (RecommendedRetailPrice*(1+(TaxRate/100))) > 50 THEN 0 ELSE 6.95 END) AS SendCosts, MarketingComments, CustomFields, SI.Video,
            (SELECT ImagePath FROM stockgroups_gebruiker JOIN stockitemstockgroups_gebruiker USING(StockGroupID) WHERE StockItemID = SI.StockItemID LIMIT 1) as BackupImagePath   
            FROM stockitems_gebruiker SI 
            JOIN stockitemholdings_gebruiker SIH USING(stockitemid)
            JOIN stockitemstockgroups_gebruiker ON SI.StockItemID = stockitemstockgroups_gebruiker.StockItemID
            JOIN stockgroups_gebruiker USING(StockGroupID)
            WHERE SI.stockitemid = ?
            GROUP BY StockItemID";

    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_bind_param($Statement, "i", $id);
    mysqli_stmt_execute($Statement);
    $ReturnableResult = mysqli_stmt_get_result($Statement);
    if ($ReturnableResult && mysqli_num_rows($ReturnableResult) == 1) {
        $Result = mysqli_fetch_all($ReturnableResult, MYSQLI_ASSOC)[0];
    }

    return $Result;
}
//  Deze functie haalt alle informatie over een stockitem uit de database

function getStockItemImage($id, $databaseConnection) {

    $Query = "
                SELECT ImagePath
                FROM stockitemimages_gebruiker 
                WHERE StockItemID = ?";

    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_bind_param($Statement, "i", $id);
    mysqli_stmt_execute($Statement);
    $R = mysqli_stmt_get_result($Statement);
    $R = mysqli_fetch_all($R, MYSQLI_ASSOC);

    return $R;
}
// Deze functie zoekt de juiste afbeelding bij een StockItem

$dbTemp = mysqli_connect("localhost", "root", "", "nerdygadgets");

function getTemperature ($databaseConnection) {

    $Query = "
                SELECT temperature
                FROM coldroomtemperatures_gebruiker
                WHERE ColdRoomTemperatureID = (
                SELECT MAX(ColdRoomTemperatureID)
                FROM coldroomtemperatures_gebruiker)";

    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_execute($Statement);
    $result = mysqli_stmt_get_result($Statement);
    if($result->num_rows == 0){
        return false;
    }
    return $result->fetch_row()[0];
}
// Deze functie haalt de meest recent gemeten temperatuur uit de database

function getIsChillerStock($id, $databaseConnection)
{

    $Query = "
                SELECT IsChillerStock
                FROM stockitems_gebruiker 
                WHERE StockItemID = ?";

    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_bind_param($Statement, "i", $id);
    mysqli_stmt_execute($Statement);
    $result = mysqli_stmt_get_result($Statement);
    if($result->num_rows == 0){
        return 0;
    }
    return $result->fetch_row()[0];
}
// Deze functie kijkt of een StockItem gekoeld is of niet

function getTemperatureCount ($databaseConnection_admin) {

    $Query = "
                SELECT count(*)
                FROM coldroomtemperatures_gebruiker";

    $Statement = mysqli_prepare($databaseConnection_admin, $Query);
    mysqli_stmt_execute($Statement);
    $result = mysqli_stmt_get_result($Statement);
    return $result->fetch_row()[0];
}
// Deze functie telt het aantal gemeten temperaturen in de coldroomtemperatures tabel

function archiveTemperature ($databaseConnection_admin) {

    $Query = "
                INSERT INTO coldroomtemperatures_archive SELECT * FROM coldroomtemperatures WHERE ColdRoomTemperatureID = (SELECT min(ColdRoomTemperatureID) FROM coldroomtemperatures);";

    $Statement = mysqli_prepare($databaseConnection_admin, $Query);
    mysqli_stmt_execute($Statement);
    if (mysqli_affected_rows($databaseConnection_admin) > 0) {
        deleteArchivedTemperature($databaseConnection_admin);
    } else {
        return false;
    }
}
// Deze functie archiveert de oudste temperatuur in coldroomtemperatures

function deleteArchivedTemperature ($databaseConnection_admin)
{

    $Query = "
                DELETE FROM coldroomtemperatures WHERE ColdRoomTemperatureID = (SELECT min(ColdRoomTemperatureID) FROM coldroomtemperatures);";

    $Statement = mysqli_prepare($databaseConnection_admin, $Query);
    mysqli_stmt_execute($Statement);
}
// Deze functie delete de gearchiveerde temperatuur

?>
