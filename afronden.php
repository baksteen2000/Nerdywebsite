<?php
include __DIR__ . "/header.php";
?>

    <div class="container container-sm" style="padding-top: 3em;">
        <h1 class="text-center">Bestelling Afronden</h1>
        <div class="text-center">
            <?php
            //Als de cart leeg is, krijgt de bezoeker/klant een melding
            if(!isset($_SESSION['cart'])){
                die("<div class='container container-sm'><div class='text-center alert alert-danger'>Je moet eerst wat in je winkelmand plaatsen. <a href='index.php'>Verder Winkelen</a></div></div>");
            }
            if(empty($_SESSION['cart'])){
                die("<div class='container container-sm'><div class='text-center alert alert-danger'>Je moet eerst wat in je winkelmand plaatsen. <a href='index.php'>Verder Winkelen</a></div></div>");
            }
            $cart = $_SESSION['cart'];
            //Als er wel iets in de cart zit, kan de klant kiezen hoe die verder wil gaam
            ?>
            <?php if(isset($_SESSION['login'])) { ?>
            <a role="button" class="btn btn-success text-light" href="afrekenen.php?type=account">Ga door als <?php print(getFirstname($databaseConnection, $_SESSION['login'])); ?></a>
                <br><br><a href="afrekenen.php?type=guest">Ga door als gast</a>
    <?php }else{ ?>
                <a role="button" class="btn btn-success text-light" href="afrekenen.php?type=guest">Ga door als gast</a>
                <br><br><a href="inloggen.php">Heb je een account? Log dan in.</a>
                <br><br><a <button class="btn btn-primary winkelmand-toevoegen-knop" onclick="<?php placeOrder($databaseConnection, $_SESSION['cart'], 1)?>">Bestellen</button></a>
    <?php } ?>
        </div>
    </div>

<?php
include __DIR__ . "/footer.php";

?>