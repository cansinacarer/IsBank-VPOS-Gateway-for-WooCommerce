<!--<h4>3D Dönen Parametreler</h4>
    <table border="1">
        <tr>
            <td><b>Parametre Ismi</b></td>
            <td><b>Parametre Degeri</b></td>
        </tr>
-->
<?php

    error_reporting(0);

    $odemeparametreleri = array("AuthCode","Response","HostRefNum","ProcReturnCode","TransId","ErrMsg"); 
    foreach($_POST as $key => $value)
    {
        $check=0;
        for($i=0;$i<6;$i++)
        {
            if($key == $odemeparametreleri[$i])
            {
                $check=0;
                break;
            }   
        }
        if($check == 1)
        {
            echo "<tr><td>".$key."</td><td>".$value."</td></tr>";
        }
    }

?>
<!--
</table>
<br>
<br>
-->
<?php

     /* Yollanan hash ve gelen parametrelerle yeni olusturulan hash kontrol ediliyor */ 


     $hashparams = $_POST["HASHPARAMS"];
     $hashparamsval = $_POST["HASHPARAMSVAL"];
     $hashparam = $_POST["HASH"];
     $storekey=$_SESSION["storekey"];
     $paramsval="";
     $index1=0;
     $index2=0;

     while($index1 < strlen($hashparams))
     {
        $index2 = strpos($hashparams,":",$index1);
        $vl = $_POST[substr($hashparams,$index1,$index2- $index1)];
        if($vl == null)
        $vl = "";
        $paramsval = $paramsval . $vl; 
        $index1 = $index2 + 1;
    }
    $storekey = $_SESSION["storekey"];
    $hashval = $paramsval.$storekey;


    

    $hash = base64_encode(pack('H*',sha1($hashval)));
    
    if($paramsval != $hashparamsval || $hashparam != $hash)     
        //echo "<h4>Güvenlik Uyarisi. Sayisal Imza Geçerli Degil</h4>";



    $mdStatus = $_POST["mdStatus"];
    $ErrMsg = $_POST["ErrMsg"];
    if($mdStatus == 1 || $mdStatus == 2 || $mdStatus == 3 || $mdStatus == 4)
    {
        //echo "<h4>3D Islemi basarili</h4><br/>";



?>
<!--
        <h4> Ödeme Sonucu</h4>
            <table border="1">
                <tr>
                    <td><b>Parametre Ismi</b></td>
                    <td><b>Parameter Degeri</b></td>
                </tr>
-->
<?php
        
        for($i=0;$i<6;$i++)
        {
            $param = $odemeparametreleri[$i];
            //echo "<tr><td>".$param."</td><td>".$_POST[$param]."</td></tr>";

        }
?></table><?php


        
        $response = $_POST["Response"];

        
        if($response == "Approved")
        {
            //echo "Ödeme Islemi Basarili";

        session_start();
            $_SESSION["yanit"]          = 'basarili';
            $_SESSION["provizyonNo"]    = $_POST["AuthCode"];
            $_SESSION["donenOid"]       = $_POST["ReturnOid"];
            echo "  
                <script>
                    window.top.location.href = '" . $_SESSION["okUrl"] . "';
                </script>
            ";
        }
        else
        {
            //echo "Ödeme Islemi Basarisiz. Hata: ".$ErrMsg;

        session_start();
            $_SESSION["yanit"]      = 'basarisiz';
            $_SESSION["hataMesaji"] = $ErrMsg;
            echo "  
                <script>
                    window.top.location.href = '" . $_SESSION["returnUrl"] . "';
                </script>
            ";
        }
        
    }
    else
    {
        //echo "<h5>3D Islemi basarisiz</h5>";

        session_start();
        $_SESSION["yanit"]      = 'basarisiz';
        $_SESSION["hataMesaji"] = '3D Islemi basarisiz';
        echo "  
            <script>
                window.top.location.href = '" . $_SESSION["returnUrl"] . "';
            </script>
        ";
    }


