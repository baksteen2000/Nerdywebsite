<!-- dit bestand bevat alle code voor het productoverzicht -->
<?php
include __DIR__ . "/header.php";
include 'cartfuncties.php';
//Dit zijn de standaard waarden waarmee de site zoekt
$ReturnableResult = null;
$Sort = "SellPrice";
$SortName = "price_low_high";

$AmountOfPages = 0;
$queryBuildResult = "";

//dit zorgt ervoor voor als je op id zoekt je het alleen het bijbehorende product krijgt
if (isset($_GET['category_id'])) {
    $CategoryID = $_GET['category_id'];
} else {
    $CategoryID = "";
}
//Dit zorgt voor de hoeveelheid producten op een pagina
if (isset($_GET['products_on_page'])) {
    $ProductsOnPage = $_GET['products_on_page'];
    $_SESSION['products_on_page'] = $_GET['products_on_page'];
} else if (isset($_SESSION['products_on_page'])) {
    $ProductsOnPage = $_SESSION['products_on_page'];
} else {
    $ProductsOnPage = 25;
    $_SESSION['products_on_page'] = 25;
}
//dit haalt het pagina nummer naar voren
if (isset($_GET['page_number'])) {
    $PageNumber = $_GET['page_number'];
} else {
    $PageNumber = 0;
}

// code deel 1 van User story: Zoeken producten
// <voeg hier de code in waarin de zoekcriteria worden opgebouwd>
//dit is de standaard zoek waarde
$SearchString = "";

if (isset($_GET['search_string'])) {
    $SearchString = $_GET['search_string'];
}
if (isset($_GET['sort'])) {
    $SortOnPage = $_GET['sort'];
    $_SESSION["sort"] = $_GET['sort'];
} else if (isset($_SESSION["sort"])) {
    $SortOnPage = $_SESSION["sort"];
} else {
    $SortOnPage = "price_low_high";
    $_SESSION["sort"] = "price_low_high";
}
//dit stukje haalt meerdere opties naar voren met hoe je kunt zoeken
switch ($SortOnPage) {
    case "price_high_low":
    {
        $Sort = "SellPrice DESC";
        break;
    }
    case "name_low_high":
    {
        $Sort = "StockItemName";
        break;
    }
    case "name_high_low";
        $Sort = "StockItemName DESC";
        break;
    case "price_low_high":
    {
        $Sort = "SellPrice";
        break;
    }
    case "weight_high_low";
    {
        $Sort = "TypicalWeightPerUnit DESC";
        break;
    }
    case "weight_low_high";
    {
        $Sort = "TypicalWeightPerUnit";
        break;
    }
    default:
    {
        $Sort = "SellPrice";
        $SortName = "price_low_high";
    }
}
$searchValues = explode(" ", $SearchString);

//dit deel zoekt op basis van de text die je hebt ingevuld
$queryBuildResult = "";
if ($SearchString != "") {
    for ($i = 0; $i < count($searchValues); $i++) {
        if ($i != 0) {
            $queryBuildResult .= "AND ";
        }
        $queryBuildResult .= "SI.SearchDetails LIKE '%$searchValues[$i]%' ";
    }
    if ($queryBuildResult != "") {
        $queryBuildResult .= " OR ";
    }
    if ($SearchString != "" || $SearchString != null) {
        $queryBuildResult .= "SI.StockItemID ='$SearchString'";
    }
}

// <einde van de code voor zoekcriteria>
// einde code deel 1 van User story: Zoeken producten


$Offset = $PageNumber * $ProductsOnPage;

if ($CategoryID != "") {
    if ($queryBuildResult != "") {
        $queryBuildResult .= " AND ";
    }
}

// code deel 2 van User story: Zoeken producten
// <voeg hier de code in waarin het zoekresultaat opgehaald wordt uit de database>

if ($CategoryID == "") {
    if ($queryBuildResult != "") {
        $queryBuildResult = "WHERE " . $queryBuildResult;
    }
//deze query haalt informatie naar voren die wordt gebruikt voor de informatie bij de producten
    $Query = "
                SELECT avg(RE.Stars) 'Stars', SI.StockItemID, SI.StockItemName, SI.MarketingComments, TaxRate, RecommendedRetailPrice, ROUND(TaxRate * RecommendedRetailPrice / 100 + RecommendedRetailPrice,2) as SellPrice,
                QuantityOnHand,
                (SELECT ImagePath
                FROM stockitemimages_gebruiker
                WHERE StockItemID = SI.StockItemID LIMIT 1) as ImagePath,
                (SELECT ImagePath FROM stockgroups_gebruiker JOIN stockitemstockgroups_gebruiker USING(StockGroupID) WHERE StockItemID = SI.StockItemID LIMIT 1) as BackupImagePath
                FROM stockitems_gebruiker SI
                JOIN stockitemholdings_gebruiker SIH USING(stockitemid)
                LEFT JOIN reviews_gebruiker RE USING (stockitemid)
                " . $queryBuildResult . "
                GROUP BY StockItemID
                ORDER BY " . $Sort . "
                LIMIT ?  OFFSET ?";


    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_bind_param($Statement, "ii",  $ProductsOnPage, $Offset);
    mysqli_stmt_execute($Statement);
    $ReturnableResult = mysqli_stmt_get_result($Statement);
    $ReturnableResult = mysqli_fetch_all($ReturnableResult, MYSQLI_ASSOC);

//Dit telt de hoeveelheid items die de gebruiker heeft geselecteerd
    $Query = "
            SELECT count(*)
            FROM stockitems_gebruiker SI
            $queryBuildResult";
    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_execute($Statement);
    $Result = mysqli_stmt_get_result($Statement);
    $Result = mysqli_fetch_all($Result, MYSQLI_ASSOC);
}

// <einde van de code voor zoekresultaat>
// einde deel 2 van User story: Zoeken producten

//dit haalt nog meer informatie over de producten naar voren
if ($CategoryID !== "") {
    $Query = "
           SELECT avg(RE.Stars) 'Stars', SI.StockItemID, SI.StockItemName, SI.MarketingComments, TaxRate, RecommendedRetailPrice,
           ROUND(SI.TaxRate * SI.RecommendedRetailPrice / 100 + SI.RecommendedRetailPrice,2) as SellPrice,
           QuantityOnHand,
           (SELECT ImagePath FROM stockitemimages_gebruiker WHERE StockItemID = SI.StockItemID LIMIT 1) as ImagePath,
           (SELECT ImagePath FROM stockgroups_gebruiker JOIN stockitemstockgroups_gebruiker USING(StockGroupID) WHERE StockItemID = SI.StockItemID LIMIT 1) as BackupImagePath
           FROM stockitems_gebruiker SI
           JOIN stockitemholdings_gebruiker SIH USING(stockitemid)
           JOIN stockitemstockgroups_gebruiker USING(StockItemID)
           JOIN stockgroups_gebruiker ON stockitemstockgroups_gebruiker.StockGroupID = stockgroups_gebruiker.StockGroupID
           LEFT JOIN reviews_gebruiker RE USING (stockitemid)
           WHERE " . $queryBuildResult . " ? IN (SELECT StockGroupID from stockitemstockgroups_gebruiker WHERE StockItemID = SI.StockItemID)
           GROUP BY StockItemID
           ORDER BY " . $Sort . "
           LIMIT ? OFFSET ?";

    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_bind_param($Statement, "iii", $CategoryID, $ProductsOnPage, $Offset);
    mysqli_stmt_execute($Statement);
    $ReturnableResult = mysqli_stmt_get_result($Statement);
    $ReturnableResult = mysqli_fetch_all($ReturnableResult, MYSQLI_ASSOC);

    $Query = "
                SELECT count(*)
                FROM stockitems_gebruiker SI
                WHERE " . $queryBuildResult . " ? IN (SELECT SS.StockGroupID from stockitemstockgroups_gebruiker SS WHERE SS.StockItemID = SI.StockItemID)";
    $Statement = mysqli_prepare($databaseConnection, $Query);
    mysqli_stmt_bind_param($Statement, "i", $CategoryID);
    mysqli_stmt_execute($Statement);
    $Result = mysqli_stmt_get_result($Statement);
    $Result = mysqli_fetch_all($Result, MYSQLI_ASSOC);
}
$amount = $Result[0];
if (isset($amount)) {
    $AmountOfPages = ceil($amount["count(*)"] / $ProductsOnPage);
}


//dit laat zien hoeveel er op voorraad is of dat er genoeg is
function getVoorraadTekst($actueleVoorraad) {
    if ($actueleVoorraad >= 1000) {
        return "Ruime voorraad beschikbaar.";
    } else {
        return "Voorraad: $actueleVoorraad";
    }
}
//dit berekent de prijs
function berekenVerkoopPrijs($adviesPrijs, $btw) {
    $verkoopPrijs = $btw * $adviesPrijs / 100 + $adviesPrijs;
    if (($verkoopPrijs) < 0) {
        return -1;
    } else {
        return $verkoopPrijs;
    }
}
?>

<!-- code deel 3 van User story: Zoeken producten : de html -->
<!-- de zoekbalk links op de pagina  -->
<?php //dit deel geeft laat de zoek balk zien en de manieren waarop je kunt zoeken ?>
<div id="FilterFrame"><h4 class="FilterText"><i class="fas fa-filter"></i> Filteren </h4>
    <form>
        <div id="FilterOptions">
            <h6 class="FilterTopMargin"><i class="fas fa-search"></i> Zoeken</h6>
            <input type="text" name="search_string" id="search_string"
                   value="<?php print (isset($_GET['search_string'])) ? $_GET['search_string'] : ""; ?>"
                   class="form-submit">
            <h6 class="FilterTopMargin"><i class="fas fa-list-ol"></i> Aantal producten op pagina</h6>

            <input type="hidden" name="category_id" id="category_id"
                   value="<?php print (isset($_GET['category_id'])) ? $_GET['category_id'] : ""; ?>">
            <select name="products_on_page" id="products_on_page" onchange="this.form.submit()">>
                <option value="25" <?php if ($_SESSION['products_on_page'] == 25) {
                    print "selected";
                } ?>>25
                </option>
                <option value="50" <?php if ($_SESSION['products_on_page'] == 50) {
                    print "selected";
                } ?>>50
                </option>
                <option value="75" <?php if ($_SESSION['products_on_page'] == 75) {
                    print "selected";
                } ?>>75
                </option>
            </select>
            <h6 class="FilterTopMargin"><i class="fas fa-sort"></i> Sorteren</h6>
            <select name="sort" id="sort" onchange="this.form.submit()">>
                <option value="price_low_high" <?php if ($_SESSION['sort'] == "price_low_high") {
                    print "selected";
                } ?>>Prijs oplopend
                </option>
                <option value="price_high_low" <?php if ($_SESSION['sort'] == "price_high_low") {
                    print "selected";
                } ?> >Prijs aflopend
                </option>
                <option value="name_low_high" <?php if ($_SESSION['sort'] == "name_low_high") {
                    print "selected";
                } ?>>Naam oplopend
                </option>
                <option value="name_high_low" <?php if ($_SESSION['sort'] == "name_high_low") {
                    print "selected";
                } ?>>Naam aflopend
                </option>
                <option value="weight_low_high" <?php if ($_SESSION['sort'] == "weight_low_high") {
                    print "selected";
                } ?>>Gewicht oplopend
                </option>
                <option value="weight_high_low" <?php if ($_SESSION['sort'] == "weight_high_low") {
                    print "selected";
                } ?>>Gewicht aflopend
                </option>
            </select>
    </form>
</div>
</div>

<!-- einde zoekresultaten die links van de zoekbalk staan -->
<!-- einde code deel 3 van User story: Zoeken producten  -->
<?php //dit stuk laat de producten zien en de informatie die erbij hoort?>
<div id="ResultsArea" class="Browse">
    <?php
    if (isset($_POST["submit"])) {              // zelfafhandelend formulier
        $stockItemID = $_POST["stockItemID"];
        addProductToCart($stockItemID);         // maak gebruik van geïmporteerde functie uit cartfuncties.php
        print("<div class='alert alert-info'>Je hebt een artikel aan je <a href='cart.php'> winkelmand</a> toegevoegd.</div>");
    }
    ?>
    <?php
    if (isset($ReturnableResult) && count($ReturnableResult) > 0) {
        foreach ($ReturnableResult as $row) {
            ?>
            <!--  coderegel 1 van User story: bekijken producten  -->

                <!-- einde coderegel 1 van User story: bekijken producten   -->
                <div id="ProductFrame">
                    <?php
                    if (isset($row['ImagePath'])) { ?>
                        <div class="ImgFrame"
                             style="background-image: url('<?php print "Public/StockItemIMG/" . $row['ImagePath']; ?>'); background-size: 230px; background-repeat: no-repeat; background-position: center;"></div>
                    <?php } else if (isset($row['BackupImagePath'])) { ?>
                        <div class="ImgFrame"
                             style="background-image: url('<?php print "Public/StockGroupIMG/" . $row['BackupImagePath'] ?>'); background-size: cover;"></div>
                    <?php }
                    ?>

                    <div id="StockItemFrameRight">
                        <div class="CenterPriceLeftChild">
                            <h1 class="StockItemPriceText"><?php
                                $prijs1 = berekenVerkoopPrijs($row["RecommendedRetailPrice"], $row["TaxRate"]);
                                if($prijs1 <= 0){
                                    print("Niet leverbaar");
                                }else{
                                    print sprintf(" €%0.2f", $prijs1);
                                    print("</h1> <h6>Inclusief BTW </h6>");
                                }

                                ?>
                        </div>
                    </div>
                    <h1 class="StockItemID">Artikelnummer: <?php print $row["StockItemID"]; ?></h1>
                    <p class="StockItemName"><?php print $row["StockItemName"]; ?></p>
                    <?php
                    $rating = 0;
                    if($row["Stars"] != null){
                        $rating = $row["Stars"];
                    }

                    $rate = round($rating, 1);
                    $rateFloored = floor($rating);
                    if($rate == 0){
                        $rate = "Dit product is nog niet beoordeeld.";
                    }else{
                        $rate = $rate . "/5 <img src='Public/Img/" . $rateFloored . "_Out_Of_5.png' style='max-width: 100px; max-height: 100px;'>";
                    }
                    ?>
                    <p class="StockItemComments"><?php print $rate; ?></p>
                    <h4 class="ItemQuantity"><?php
                        if(!($prijs1 == -1)){
                            print getVoorraadTekst($row["QuantityOnHand"]);
                        }
                        ?><div class="winkelmand-knop" style="padding-top: 15px!important">
                            <form method="post">
                                <input type="number" name="stockItemID" value="<?php print($row['StockItemID']) ?>" hidden>
                                <input type="submit" class="btn btn-danger winkelmand-toevoegen-knop text-light" name="submit" value="Voeg toe aan winkelmand">
                            </form>
                        </div></h4>
                </div>
                <!--  coderegel 2 van User story: bekijken producten  -->
            <!--  einde coderegel 2 van User story: bekijken producten  -->
        <?php } ?>

        <form id="PageSelector">

            <!-- code deel 4 van User story: Zoeken producten  -->

            <?php //dit deel zoekt op de waardes die zijn geselecteerd of ingevoerd ?>
            <input type="hidden" name="search_string" id="search_string"
                   value="<?php if (isset($_GET['search_string'])) {
                       print ($_GET['search_string']);
                   } ?>">
            <input type="hidden" name="sort" id="sort" value="<?php print ($_SESSION['sort']); ?>">

            <!-- einde code deel 4 van User story: Zoeken producten  -->
            <input type="hidden" name="category_id" id="category_id" value="<?php if (isset($_GET['category_id'])) {
                print ($_GET['category_id']);
            } ?>">
            <input type="hidden" name="result_page_numbers" id="result_page_numbers"
                   value="<?php print (isset($_GET['result_page_numbers'])) ? $_GET['result_page_numbers'] : "0"; ?>">
            <input type="hidden" name="products_on_page" id="products_on_page"
                   value="<?php print ($_SESSION['products_on_page']); ?>">

            <?php
            if ($AmountOfPages > 0) {
                for ($i = 1; $i <= $AmountOfPages; $i++) {
                    if ($PageNumber == ($i - 1)) {
                        ?>
                        <div id="SelectedPage"><?php print $i; ?></div><?php
                    } else { ?>
                        <button id="page_number" class="PageNumber" value="<?php print($i - 1); ?>" type="submit"
                                name="page_number"><?php print($i); ?></button>
                    <?php }
                }
            }
            ?>
        </form>
        <?php
    } else {
        ?>
        <h2 id="NoSearchResults">
            Er zijn geen resultaten gevonden.
        </h2>
        <?php
    }
    ?>
</div>

<?php
include __DIR__ . "/footer.php";
?>