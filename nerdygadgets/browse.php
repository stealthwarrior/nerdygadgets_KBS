<!-- dit bestand bevat alle code voor het productoverzicht -->
<?php
include __DIR__ . "/header.php";

$ReturnableResult = null;
$Sort = "SellPrice";
$SortName = "price_low_high";

$AmountOfPages = 0;
$queryBuildResult = "";


if (isset($_GET['category_id'])) {
    $CategoryID = $_GET['category_id'];
} else {
    $CategoryID = "";
}
if (isset($_GET['products_on_page'])) {

    $_GET["products_on_page"] = intval($_GET["products_on_page"]);
    $ProductsOnPage = $_GET['products_on_page'];
    $_SESSION['products_on_page'] = $_GET['products_on_page'];
} else if (isset($_SESSION['products_on_page'])) {
    $ProductsOnPage = $_SESSION['products_on_page'];
} else {
    $ProductsOnPage = 25;
    $_SESSION['products_on_page'] = 25;
}
if (isset($_GET['page_number'])) {
    $PageNumber = $_GET['page_number'];
} else {
    $PageNumber = 0;
}

// code deel 1 van User story: Zoeken producten
// <voeg hier de code in waarin de zoekcriteria worden opgebouwd>



// <einde van de code voor zoekcriteria>
// einde code deel 1 van User story: Zoeken producten
//$ProductsOnPage = 10;

$Offset = $PageNumber * $ProductsOnPage;


if ($CategoryID != "") { 
    if ($queryBuildResult != "") {
    $queryBuildResult .= " AND ";
    }
}

// code deel 2 van User story: Zoeken producten
// <voeg hier de code in waarin het zoekresultaat opgehaald wordt uit de database>



// <einde van de code voor zoekresultaat>
// einde deel 2 van User story: Zoeken producten

if ($CategoryID !== "") {

$Query = "
           SELECT SI.StockItemID, SI.StockItemName, SI.MarketingComments, TaxRate, RecommendedRetailPrice,
           ROUND(SI.TaxRate * SI.RecommendedRetailPrice / 100 + SI.RecommendedRetailPrice,2) as SellPrice,
           QuantityOnHand,
           (SELECT ImagePath FROM stockitemimages WHERE StockItemID = SI.StockItemID LIMIT 1) as ImagePath,
           (SELECT ImagePath FROM stockgroups JOIN stockitemstockgroups USING(StockGroupID) WHERE StockItemID = SI.StockItemID LIMIT 1) as BackupImagePath
           FROM stockitems SI
           JOIN stockitemholdings SIH USING(stockitemid)
           JOIN stockitemstockgroups USING(StockItemID)
           JOIN stockgroups ON stockitemstockgroups.StockGroupID = stockgroups.StockGroupID
           WHERE " . $queryBuildResult . " ? IN (SELECT StockGroupID from stockitemstockgroups WHERE StockItemID = SI.StockItemID)
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
                FROM stockitems SI
                WHERE " . $queryBuildResult . " ? IN (SELECT SS.StockGroupID from stockitemstockgroups SS WHERE SS.StockItemID = SI.StockItemID)";
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


    function getVoorraadTekst($actueleVoorraad) {
        if ($actueleVoorraad > 1000) {
            return "Ruime voorraad beschikbaar.";
        } else {
            return "Voorraad: $actueleVoorraad";
        }
    }
    function berekenVerkoopPrijs($adviesPrijs, $btw) {
		return $btw * $adviesPrijs / 100 + $adviesPrijs;
    }
?>

<!-- code deel 3 van User story: Zoeken producten : de html -->
<!-- de zoekbalk links op de pagina  -->



<!-- einde zoekresultaten die links van de zoekbalk staan -->
<!-- einde code deel 3 van User story: Zoeken producten  -->

<!-- instellen Aantal Paginas  code-->
<form action="#"  method="get" class="paginas_form" >
    <select id="products_on_page" name="products_on_page" >
      <?php
        $paginas = 25;
        for ($i=0; $i <3; $i++){
            echo "<option value='$paginas'> $paginas</option>";
            $paginas += 25;
        }
      ?>
        <input type="hidden" name="category_id" value="<?php echo $_GET["category_id"]; ?>">

    </select>
    <input type="submit"value="kies">
</form>

<!--Soorteren op prijs (en straks op naam) form-->
<form action="#"  method="get" class="soorteren" >
    <select id="soorteren_opties" name="soorteren_opties">
        <option value="prijs">
            prijs
        </option>
        <option value="naam">
            naam

        </option>

    </select>
    <input type="hidden" name="category_id" value="<?php echo $_GET["category_id"]; ?>">

<input type="submit"value="kies">
</form>


<div id="ResultsArea" class="Browse">
    <?php
# Soorteren op prijs Code
    if(isset($_GET["soorteren_opties"])){
        if($_GET["soorteren_opties"] == "prijs"){
            $grootstePrijs = 0;
            $soorteren_op_prijs = array();
            $producten_id = array();

            foreach ( $ReturnableResult as $row){
                if (intval($row["SellPrice"]) > $grootstePrijs) {
                    $grootstePrijs = intval($row["SellPrice"]);


                    array_unshift($soorteren_op_prijs, $row["SellPrice"]);
                    array_unshift($producten_id,$row["StockItemID"]);

                } else{
                    $i =0 ;
                    while (in_array($soorteren_op_prijs[$i],$soorteren_op_prijs) && intval($row["SellPrice"]) < intval($soorteren_op_prijs[$i]) ){
                        $i++;

                    }

                        array_splice($soorteren_op_prijs, $i,0,$row["SellPrice"]);
                        array_splice($producten_id, $i,0, $row["StockItemID"]);

                }
            }
            $gesorteerd_producten=[];

            foreach ($producten_id as $id){
                foreach ($ReturnableResult as $row){
                    if ($id == $row["StockItemID"]){
                        array_push($gesorteerd_producten, $row);

                        break;
                    }

                }
            }

            $ReturnableResult = $gesorteerd_producten;

        } elseif ($_GET["soorteren_opties"] == "naam"){
            usort($ReturnableResult, function ($a, $b) {
                return strtolower($a["StockItemName"]) <=> strtolower($b["StockItemName"]);
            });

        }
    }





    if (isset($ReturnableResult) && count($ReturnableResult) > 0) {

        foreach ($ReturnableResult as $row) {
            ?>
            <!--  coderegel 1 van User story: bekijken producten  -->
            <a class="ListItem" href='view.php?id=<?php print $row['StockItemID']; ?>'>



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
                            <h1 class="StockItemPriceText"><?php print sprintf(" %0.2f", berekenVerkoopPrijs($row["RecommendedRetailPrice"], $row["TaxRate"])); ?></h1>
                            <h6>Inclusief BTW </h6>
                        </div>
                    </div>
                    <h1 class="StockItemID">Artikelnummer: <?php print $row["StockItemID"]; ?></h1>
                    <p class="StockItemName"><?php print $row["StockItemName"]; ?></p>
                    <p class="StockItemComments"><?php print $row["MarketingComments"]; ?></p>
                    <h4 class="ItemQuantity"><?php print getVoorraadTekst($row["QuantityOnHand"]); ?></h4>
                </div>
            <!--  coderegel 2 van User story: bekijken producten  -->
            </a>



            <!--  einde coderegel 2 van User story: bekijken producten  -->
        <?php } ?>

        <form id="PageSelector">

<!-- code deel 4 van User story: Zoeken producten  -->



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
            Yarr, er zijn geen resultaten gevonden.
        </h2>
        <?php
    }
    ?>
</div>

<?php
include __DIR__ . "/footer.php";
?>
