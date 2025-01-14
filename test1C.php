<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?
global $USER;
if($USER->IsAdmin()){
  //  $ex = new \Webfly\Download\UserFrom1C();
 //   $ex->getUserOpenLines();

     $ex = new \Webfly\Generate\GeneratePhotoProduct();
      $ex->execute();
}

?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
