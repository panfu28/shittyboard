<?php
include 'settings.php';
$cssSelect="";
function htmlMeta(){
    global $sBoardName, $css, $cssSelect;
    echo "<!DOCTYPE html>";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
    header('Cache-Control: max-age=600');
    echo "<head><title>$sBoardName</title>";
    $sheets=glob("$css/*.css");
    if(htmlspecialchars($_GET["css"])!==""){
        setcookie("css", htmlspecialchars($_GET["css"]) , time()+3600*24*90);
        redirect($_SERVER[PHP_SELF]);
    }
    ;
    if (array_key_exists($_COOKIE["css"], $sheets)){
        echo '<link rel="stylesheet" type="text/css" href="'.$sheets[$_COOKIE["css"]].'">';
    }else{
        setcookie("css",0);
        echo '<link rel="stylesheet" type="text/css" href="'.$sheets[0].'">';
    }
    echo "</head>";
    foreach(array_keys($sheets) as $x){
    $cssSelect=$cssSelect."<a href=\"".$_SERVER[PHP_SELF]."?css=$x\">[Style $x]</a>";
    }
    $cssSelect=$cssSelect."<br>";
}
function loadCookies(){
    global $key, $cooldown, $initialCooldown;
    if (!isset($_COOKIE["posts"]) || !isset($_COOKIE["lastPostTime"])){
        setcookie("posts", openssl_encrypt(0, "AES-128-CBC", $key), time()+3600*24*90);
        $tmp=time();
        setcookie("lastPostTime", openssl_encrypt($tmp+$initialCooldown-$cooldown, "AES-128-CBC", $key), time()+3600*24*90);
        
        redirect($_SERVER[PHP_SELF]);
    }
    $cookiePosts=openssl_decrypt($_COOKIE["posts"], "AES-128-CBC", $key);
    $cookieLastTime=openssl_decrypt($_COOKIE["lastPostTime"], "AES-128-CBC", $key);
    if ($cookieLastTime===FALSE || $cookiePosts===FALSE){
        setcookie("posts", openssl_encrypt(0, "AES-128-CBC", $key), time()+3600*24*90);
        $tmp=time();
        setcookie("lastPostTime", openssl_encrypt($tmp+$initialCooldown-$cooldown, "AES-128-CBC", $key), time()+3600*24*90);
        redirect($_SERVER[PHP_SELF]);
    }else{
        session_start();
        $_SESSION["postQuantity"]=$cookiePosts;
        $_SESSION["lastPostTime"]=$cookieLastTime;
    }
}
function writeCookie(){
    global $key;
    setcookie("posts", openssl_encrypt($_SESSION["postQuantity"], "AES-128-CBC", $key), time()+3600*24*90);
    setcookie("lastPostTime", openssl_encrypt($_SESSION["lastPostTime"], "AES-128-CBC", $key), time()+3600*24*90);
}
function showOP($digits, $name, $replies, $sReplies, $pictures, $webmLogo, $thumbnails, $text){
    if ($replies!==FALSE){
        echo "<p><b>#$digits (OP)</b> $name <i><a href=\"".$_SERVER['PHP_SELF']."?thread=$digits\">[$replies $sReplies]</a></i></p>";
    }else{
        echo "<p><b>#$digits (OP)</b> $name</p>";
    }
        if($img=glob("$pictures/$digits.*")){
            $ext=strtolower(pathinfo($img[0],PATHINFO_EXTENSION));
            if ($ext=="webm"){
                echo "<p><a href=\"$img[0]\"><img src=\"$webmLogo\" width=\"200\" align=\"left\"></a>$text</p><br clear=\"left\"><hr>";
            }else{
                echo "<p><a href=\"$img[0]\"><img src=\"$thumbnails/$digits.jpg\" width=\"200\" align=\"left\"></a>$text</p><br clear=\"left\"><hr>";
            }
    }else{
        echo "<p>$text</p><br clear=\"left\"><hr>";
    }
}
function showPost($digits, $name, $pictures, $webmLogo, $thumbnails, $text){
    if ($replies!==FALSE){
        echo "<p><b>#$digits</b> $name</p>";
    }else{
        echo "<p><b>#$digits (OP)</b> $name</p>";
    }
        if($img=glob("$pictures/$digits.*")){
            $ext=strtolower(pathinfo($img[0],PATHINFO_EXTENSION));
            if ($ext=="webm"){
                echo "<p><a href=\"$img[0]\"><img src=\"$webmLogo\" width=\"150\" align=\"left\"></a>$text</p><br clear=\"left\"><hr>";
            }else{
                echo "<p><a href=\"$img[0]\"><img src=\"$thumbnails/$digits.jpg\" width=\"150\" align=\"left\"></a>$text</p><br clear=\"left\"><hr>";
            }
    }else{
        echo "<p>$text</p><br clear=\"left\"><hr>";
    }
}
function redirect($url){
    ob_end_clean();
    header('Location: '.$url);
    die;
}
function showAndDie($error){
    ob_end_clean();
    echo "$error";
    die;
}
function fgetdb($file){
    $str=fgets($file);
    if (!feof($file)){
        $str=explode("\t", str_replace("\n", "",$str));
        return $str;
    }else{
        return FALSE;
    }
}
function showHeader(){
    global $sBoardName, $logo;
    echo "<body><center><a href=\"".$_SERVER['PHP_SELF']."\"><img src=$logo width=\"300\" height=\"100\"></a><h1>$sBoardName</h1><hr></center>";
}
function showForm($captcha=""){
    global $sPostReply, $sPost, $sAnonymous;
    echo "<center><h3>$sPostReply</h3>
    <form method=\"POST\" enctype=\"multipart/form-data\">
    <input name=\"postName\" size=\"100\" style=\"width:95%;\" value=\"$sAnonymous\"><br>
    <textarea name=\"postText\" cols=\"100\" rows=\"10\"style=\"width:95%;\"></textarea><br>$captcha<br>
    <input type=\"file\" name=\"postFile\"><input type=\"submit\" name=\"postButton\" value=\"$sPost\"></form><hr></center>";
}
function loadDatabase(){
    global $database, $sDatabaseError;
    if(($file=fopen("$database","r"))!==FALSE){
        while(($data=fgetdb($file))!==FAlSE){
            $databaseArray[$data[0]][0]=$data[1];
            $databaseArray[$data[0]][1]=$data[2];
            $databaseArray[$data[0]][2]=$data[3];
            $databaseArray[$data[0]][3]=$data[4];
            $databaseArray[$data[0]][4]=$data[5];
        }
        fclose($file);
        return $databaseArray;
    }else{
        showAndDie($sDatabaseError);
    }
}
function loadBans(){
    global $bans, $sDatabaseError;
    if (($file=fopen("$bans", "r"))!==FALSE){
        while(($data=fgetdb($file))!==FALSE){
            $databaseArray[$data[0]]=$data[1];
        }
        fclose($file);
        return $databaseArray;
    }else{
        showAndDie($sDatabaseError);
    }
}
function showThreads(){
    global $pictures, $sReplies, $webmLogo, $thumbnails;
    $data=loadDatabase();
    $threads=array();
    foreach ($data as $digits => $post){
        if($post[0]!="T" && $data[$post[0]][0]=="T"){
            $threads[$post[0]]+=1;
        }else if (($threads[$digits]==0)&& $post[0]=="T"){
             $threads[$digits]=0;
        }
    }
    foreach ($threads as $digits=>$replies){
        $name=$data[$digits][2];
        $text=$data[$digits][1];
        showOP($digits, $name, $replies, $sReplies, $pictures, $webmLogo, $thumbnails, $text);
    }
}
function getIp() {
    global $key, $ipsalt;
    //Encryption of IP is an overkill
    $ipaddress = '';
    if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else
        $ipaddress = "";
    return openssl_encrypt($ipaddress . $ipsalt, "AES-128-CBC", $key);
}
function showThread($dig){
    global $pictures, $sThreadNotFound, $webmLogo, $thumbnails;
    $data=loadDatabase();
    $posts=array();
    $flag=TRUE;
    if ($data[$dig]==NULL){
        showAndDie($sThreadNotFound);
    }
    if ($data[$dig][0]!="T"){
        if($data[$data[$dig][0]][0]=="T"){
            redirect($_SERVER['PHP_SELF']."?thread=".$data[$dig][0]);
        }else{
            showAndDie($sThreadNotFound);
        }
    }
    foreach ($data as $digits => $post){
        if($post[0]==$dig){
            $posts[]=$digits;
        }
    }
    showOP($dig, $data[$dig][2], false, false, $pictures, $webmLogo, $thumbnails, $data[$dig][1]);
    sort($posts);
    foreach($posts as $digits){
        showPost($digits, $data[$digits][2], $pictures, $webmLogo, $thumbnails, $data[$digits][1]);
    }
}
function getName(){
    global $sNameTooLong;
    $name=$_POST["postName"];
    if (strlen($name)>128){
        ob_end_clean();
        echo "$sNameTooLong";
        die;
    }
    return preg_replace("/[^A-Za-z0-9_ .:]/", '', $name);
}
function getPostText(){
    global $sIllegalPost, $sPostTooLong, $maxLines, $maxPostLength;
    $txt=($_POST["postText"]);
    if (strlen($txt)>$maxPostLength){
        showAndDie($sPostTooLong);
    }
    $txt=$txt."\n";
    $txt=str_replace("&", "&amp;", $txt);
    $txt=str_replace("'", "&#039;", $txt);
    $txt=str_replace("\"", "&quot;", $txt);
    $txt=str_replace("<", "&lt;", $txt);
    $txt=str_replace(">", "&gt;", $txt);
    $txt=preg_replace('/^(&gt;&gt;(.*))\n/m', '<font color="blue" class="bluetext">\\1</font>' . "\n", strip_tags($txt)); 
    $txt=preg_replace('/^(&lt;&lt;(.*))\n/m', '<font color="#ee6b00" class="orangetext">\\1</font>' . "\n", $txt);
    $txt=preg_replace('/^(&gt;(.*))\n/m', '<font color="green" class="greentext">\\1</font>' . "\n", $txt);   
    $txt=preg_replace('/^(&lt;(.*))\n/m', '<font color="red" class="redtext">\\1</font>' . "\n", $txt);
    $txt=substr($txt, 0, -1);
    $txt=str_replace("\n", "<br>", $txt);
    $txt=str_replace("\r", "", $txt);
    if (strstr($txt, "\t")){
        showAndDie($sIllegalPost);
    }else if (strstr($txt, "\u{200E}")){
        showAndDie($sIllegalPost);
    }else if (strstr($txt, "\u{200F}")){
        showAndDie($sIllegalPost);
    }else if (substr_count($txt, "<br>")>$maxLines){
        showAndDie($sIllegalPost);
    }
    return $txt;
}
function uploadImage($newPost){
    global $pictures, $thumbnails, $maxUpload, $sWrongExt;
    $check=getimagesize($_FILES["postFile"]["tmp_name"]);
    $imageFileType=strtolower(pathinfo($_FILES["postFile"]["name"], PATHINFO_EXTENSION));
    if($imageFileType!="jpg" && $imageFileType!="png" && $imageFileType!="jpeg" && $imageFileType!="gif" && $imageFileType!="webp" && $imageFileType!="webm" && $_FILES["postFile"]["name"]!=""){
        return FALSE;
    }else{
        if($check && $_FILES["postFile"]["size"] <= $maxUpload*1024){
            move_uploaded_file($_FILES["postFile"]["tmp_name"], $pictures."/".$newPost.".".$imageFileType);
            $imagick=new \Imagick(realpath($pictures."/".$newPost.".".$imageFileType));
            //$imagick->setCompressionQuality(1);
            $imagick->resizeImage(300,300,Imagick::FILTER_SINC,1,TRUE);
            $imagick->setImageBackgroundColor('white');
            $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(70);
            $imagick->setFormat("jpg");
            $imagick->writeImage("$thumbnails/$newPost.jpg");
        }elseif ($imageFileType=="webm" && $_FILES["postFile"]["size"] <= $maxUpload*1024){
            move_uploaded_file($_FILES["postFile"]["tmp_name"], $pictures."/".$newPost.".".$imageFileType);
        }
    }
}
function posting($captcha){
    global $sThreadNotFound, $sLockError, $sDatabaseError, $database, $maxThreads, $sCooldown,$cooldown, $maxUpload, $pictures, $thumbnails, $sCaptchaFail, $proCooldown, $pro, $sSeconds, $sWait, $bans;
    if ($dig=htmlspecialchars($_GET["thread"])){
        if (loadDatabase()[$dig][0]!="T"){
            ($sThreadNotFound);
        }
    }
    $postTime=time();
    if(isset($_POST["postButton"])){
        if(!$captcha){
            showAndDie($sCaptchaFail);
        }
        $postTime=time();
        $data=loadDatabase();
        $lastDigits = key($data);
        if($postTime-$_SESSION["lastPostTime"]<$cooldown && $_SESSION["postQuantity"] <= $pro){
            showAndDie($sCooldown."<br>$sWait". ($cooldown-($postTime-$_SESSION["lastPostTime"])) . " $sSeconds");
        }elseif ($postTime-$_SESSION["lastPostTime"]<$proCooldown && $_SESSION["postQuantity"] > $pro){
           showAndDie($sCooldown."<br>$sWait". ($proCooldown-($postTime-$_SESSION["lastPostTime"])) . " $sSeconds");
        }
        $txt=getPostText();
        $name=getName();
        if($dig=htmlspecialchars($_GET["thread"])){
            $newEntry=($lastDigits+1)."\t$dig\t$txt\t$name\t".getIp()."\t$postTime\n";
            $threads[]=$dig;
        }else{
            $newEntry=($lastDigits+1)."\tT\t$txt\t$name\t".getIp()."\t$postTime\n";
            $threads[]=$lastDigits+1;
        }
        foreach($data as $digits => $post){
            if (sizeof($threads)<$maxThreads+1){
                if($post[0]=="T" && !in_array($digits,$threads)){
                    $threads[]=$digits;
                }elseif (!in_array($post[0],$threads)) {
                    $threads[]=$post[0];
                }
            }
            if(in_array($post[0],$threads) && $post[0]!="T"){
                $newEntry=$newEntry."$digits\t".$post[0]."\t".$post[1]."\t".$post[2]."\t".$post[3]."\t".$post[4]."\n";
            }elseif($post[0]=="T" && in_array($digits,$threads)){
                $newEntry=$newEntry."$digits\t".$post[0]."\t".$post[1]."\t".$post[2]."\t".$post[3]."\t".$post[4]."\n";
            }else{
                $image=glob("$pictures/$digits.*");
                unlink($image[0]);
                $image=glob("$thumbnails/$digits.*");
                unlink($image[0]);
            }
            
        }
        if(file_put_contents($database, $newEntry, LOCK_EX)==FALSE){
            showAndDie($sLockError);
        }
        uploadImage($lastDigits+1);
        $_SESSION["postQuantity"]=1+$_SESSION["postQuantity"];
        $_SESSION["lastPostTime"]=$postTime;
        writeCookie();
        redirect($_SERVER['PHP_SELF']."?thread=".($lastDigits+1) );
        
    }
}

function checkBan(){
    global $sBan, $sSilentBan;
    $bans=loadBans();
    if(array_key_exists(GetIp(), $bans)){
        if(strstr($bans[GetIp()], "!!WARN!!")){
            echo "<h1>".str_replace("!!WARN!!", "", $bans[GetIp()])."</h1><br>";
        }elseif($bans[GetIp()]==""){
            showHeader();
            showForm();
            echo "$sSilentBan";
            die;
        }else{
            echo "$sBan".$bans[GetIp()];
            die;
        }
    }
}
function captchaGen(){
    global $pro;
    if($_SESSION["postQuantity"]>$pro){
        return "No captcha for you.";
    }
    if ($_SESSION["captchaError"]>3){
        showAndDie("<h1>Error: Automatic requests. </h1><br>Contact: shittyboard (at) protonmail.com");
    }
    $categories = glob("captcha/*" , GLOB_ONLYDIR);
    shuffle($categories);
    $_SESSION["correctPath"]=array_pop($categories);
    $correctName=pathinfo($_SESSION["correctPath"], PATHINFO_FILENAME);
    $incorrect[]=array_pop($categories);
    $incorrect[]=array_pop($categories);
    //var_dump($incorrect);
    $_SESSION["incorrectPath"]=$incorrect;
    $_SESSION["correctNumber"]=rand(0,99999);
    //echo $_SESSION["correctNumber"];
    return "Find image with <b>$correctName</b> and copy symbols from that image into the box below<br><img src=\"helper.php?".htmlspecialchars(SID)."\" style=\"width:auto; max-width:95%;\"><br><input name=\"captchaVerify\" autocomplete=\"off\">";
    
}
    
function captchaCheck(){
    global $pro;
    if($_SESSION["correctNumber"]==$_POST["captchaVerify"] && isset($_SESSION["correctNumber"])){
        $_SESSION["captchaError"]=0;
        return true;
    }elseif($_SESSION["postQuantity"]>$pro){
        $_SESSION["captchaError"]=0;
        return true;
    }else{
        if(isset($_POST["postButton"])){
            $_SESSION["captchaError"]+=1;
        }
        return false;
        
    }
}
function showStream(){
    global $pictures, $sReplies, $webmLogo, $thumbnails;
    $data=loadDatabase();
    $threads=array();
    foreach ($data as $digits => $post){
        $name=$post[2];
        $text=$post[1];
        echo "<p><b>#$digits</b> $name <i><a href=\"".$_SERVER['PHP_SELF']."?thread=$digits\">[$sReplies]</a></i></p>";
        if($img=glob("$pictures/$digits.*")){
            $ext=strtolower(pathinfo($img[0],PATHINFO_EXTENSION));
            if ($ext=="webm"){
                echo "<p><a href=\"$img[0]\"><img src=\"$webmLogo\" width=\"200\" align=\"left\"></a>$text</p><br clear=\"left\"><hr>";
            }else{
                echo "<p><a href=\"$img[0]\"><img src=\"$thumbnails/$digits.jpg\" width=\"200\" align=\"left\"></a>$text</p><br clear=\"left\"><hr>";
            }
        }else{
            echo "<p>$text</p><br clear=\"left\"><hr>";
        }
    }
}


loadCookies();
htmlMeta();
checkBan();
posting(captchaCheck());
if (htmlspecialchars($_GET["stats"])=="yes"){
    echo "PHP session posts: ".$_SESSION["postQuantity"];
    echo "Last post: ".$_SESSION["lastPostTime"]."<br><hr>";
}
showHeader();
if (htmlspecialchars($_GET["stream"])=="yes"){
    showStream();
}else{
    //showForm($cap=captchaGen());
    if($dig=htmlspecialchars($_GET["thread"])){
        showThread($dig);
        showForm(captchaGen());
    }else{
        showForm(captchaGen());
        echo "$sSticky";
        showThreads();
    }
    
}
echo "<center>Shittyboard V5.4 beta<br>$cssSelect<a href=\"".$_SERVER["PHP_SELF"]."?stream=yes\">[$sStream]</a><a href=\"".$_SERVER["PHP_SELF"]."?stream=no\">[$sNormal]</a><br><a href=\"$report\">[$sReport]</a><br>$sLegal</center>";

?>
