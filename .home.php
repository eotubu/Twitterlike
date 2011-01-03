<?php
require_once("inc/auth.php");
require_once("inc/connection.php");
require_once("inc/lib.php");
$akses="";
$_SESSION['error']="";
$content="";
if(!empty($_GET['id'])){
    if(cekUsername($_GET['id'])){
        $akses="tamu";
        $username=$_GET['id'];
        if(!empty($_SESSION['username'])){
            $akses="user";
        }
    }else{
        $_SESSION['error']="Username tidak ada";
        $username="";
    }
}else{
    $akses="user";
    $username=$_SESSION['username'];
}
cekAuth($akses);
if(!empty($_GET['op'])){
    if($_GET['op']=='logout'){
        logout();
    }
}
if(!empty($_GET['retweet'])){
    $rt=getTweetById($_GET['retweet']);
    $content="RT [".$rt['username']."] ".$rt['isi'];
}
if(!empty($_GET['reply'])){
    $content=$_GET['reply'];
}
if(!empty($_REQUEST['tweet'])){
    $valid=true;
    $isi=trim($_REQUEST['isi']);
    $jml=strlen($isi);
    $isi=htmlentities($isi, ENT_QUOTES,"UTF-8");
    if($jml==0){
        $_SESSION['error']="Isikan tweet anda";
        $valid=false;
    }
    if($jml>140){
        $_SESSION['error']="Jumlah karakter melebihi batas";
        $valid=false;
    }
    
    if($valid){
        $sql = "INSERT INTO `twitterlike`.`tweet` (`id`, `username`, `tglwaktu`, `isi`) VALUES (NULL, '$username', NOW(), '$isi')";
        $hasil=mysql_query($sql);
        if (!$hasil) {
            $pesan  = 'Invalid query: ' . mysql_error() . "\n";
            $pesan .= 'Whole query: ' . $sql;
            die($pesan);
        }
        $idtweet=mysql_insert_id();
        $sql="UPDATE user SET lasttweet=$idtweet WHERE username='$username'";
        $hasil=mysql_query($sql);
        if (!$hasil) {
            $pesan  = 'Invalid query: ' . mysql_error() . "\n";
            $pesan .= 'Whole query: ' . $sql;
            if($_REQUEST['op']=="ajax"){
                header('Content-type: application/xml');
                echo '<?xml version="1.0"?>';
                echo '<tweet>';
                echo '      <berhasil>false</berhasil>';
                echo '</tweet>';
                die();
            }
            die($pesan);
        }
        if(!empty($_REQUEST['op'])&&$_REQUEST['op']=="ajax"){
            header('Content-type: application/xml');
            echo '<?xml version="1.0"?>';
            echo '<tweet>';
            echo '      <berhasil>true</berhasil>';
            echo '      <username>'.$username.'</username>';
            echo '      <isi>'.$isi.'</isi>';
            echo '      <tgl>'.date('g:i A, j M Y').'</tgl>';
            echo '</tweet>';
            die();
        }
    }
}
function getTweets($nama){
    $sql="SELECT tw.id,tw.username,DATE_FORMAT(tw.tglwaktu,'%h:%i %p, %e %b %Y') tgl,tw.isi,u.image FROM tweet tw, user u where u.username=tw.username and(tw.username='$nama' or tw.username in(select userb from follow where usera='$nama')) ORDER BY tglwaktu DESC LIMIT 10";
    $hasil=query($sql);
    return $hasil;
}
$listtweet=array();
if($username!=""){
    $info=getInfoUser($username);
    $info=$info[0];
    $info['jml']=getJmlTweet($username);
    if(!empty($_GET['id'])){
        $listtweet=getTweetsByUser($username);
    }else{
        $listtweet=getTweets($username);
    }
    $listfollowing=getArrayFollowing($username);
    $listfollower=getArrayFollower($username);
    $listfollowing=$listfollowing['image'];
    $listfollower=$listfollower['image'];
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Twitter Like</title>
        <link rel="stylesheet" href="gaya.css" type="text/css" />
        <link rel="stylesheet" href="subgaya.css" type="text/css" />
        <style type="text/css">
        </style>
    </head>
    <body>
        <div id="container">
            <div id="header">
                <a id="logo" href="/"><img src="images/logo_small.png"></a>
                <?php if($akses=="user"): ?>
                <ul id="nav" class="round">
                    <li><a href="home.php">Home</a></li>
                    <li><a href="following.php">Following</a></li>
                    <li><a href="follower.php">Follower</a></li>
                    <li><a href="reply.php">Reply</a></li>
                    <li><a href="cari.php">Cari</a></li>
                    <li><a href="setting.php">Setting</a></li>
                    <li><a href="home.php?op=logout">Keluar</a></li>
                </ul>
                <?php endif; ?>
            </div>
            <div id="wrap" class="round">
                <div id="content">
                    <div id="wcontent">
                        <?php if($akses=="user"): ?>
                        <div id="posting">
                            <?php if(!empty($_SESSION['error'])): ?>
                            <p class="error"><?php echo $_SESSION['error']; ?></p>
                            <?php 
                                $_SESSION['error']="";
                                endif;
                             ?>
                            <div id="counter">140</div>
                            <h2>Apa yang terjadi?</h2>
                            <form id="posttweet" action="home.php" method="GET">
                                <textarea name="isi" id="isi" autocomplete="off" rows="2" cols="40" required autofocus><?php echo $content; ?></textarea>
                                <input id="tmbkirim" type="submit" name="tweet" value="Tweet" class="tombol"/>
                            </form>
                        </div>
                        <?php endif; ?>
                        <h2>Home</h2>
                        <?php if($username==""):?>
                            <hr/>
                            <h2>Maaf</h2>
                            <h2>Username yang anda maksud tidak ditemukan</h2>
                        <?php endif;?>
                        <ul id="daftartweet">
                            <?php foreach($listtweet as $tweet): ?>
                            <li>
                                <a href="home.php?id=<?php echo $tweet['username']; ?>"><img class="thumb" src="images/<?php echo $tweet['image']; ?>" width="48" height="48" /></a>
                                <span class="isitweet">
                                    <span class="usernm"><a href="home.php?id=<?php echo $tweet['username']; ?>"><?php echo $tweet['username']; ?></a></span>
                                    <?php echo $tweet['isi']; ?>
                                    <div class="addinfo">
                                        <span class="tgl"><?php echo $tweet['tgl']; ?></span>
                                        <span class="operasi">
                                            <?php if($tweet['username']!=$username): ?>
                                            <a href="?reply=@<?php echo $tweet['username']; ?>">reply</a>
                                            <a href="?retweet=<?php echo $tweet['id']; ?>">retweet</a>
                                            <?php endif;?>
                                        </span>
                                    </div>
                                </span>
                            </li>
                            <?php endforeach;?>
                        </ul>
                    </div>
                </div>
                <div id="sidepanel">
                    <div id="wsidepanel">
                        <?php if(!empty($info)):?>
                        <div id="infouser">
                            <img src="images/<?php echo $info['image'];?>" height="48" width="48" class="thumb"/>
                            <a href="#">
                            <span id="nama"><?php echo $username;?></span>
                            <span id="sjmltweet"><span id="jmltweet"><?php echo $info['jml'];?></span> tweets</span>
                            </a>
                            <br style="clear:both"/>
                            <p>Nama  <span id="fullname"><?php echo $info['fullname'];?></span></p>
                            <p>Email  <span id="email"><?php echo $info['email'];?></span></p>
                            <p>Bio  <span id="bio"><?php echo $info['bio'];?><span></p>
                        </div>
                        <div class="daftaricon round">
                        <p>Pengikut</p>
                        <?php foreach($listfollower as $id => $gambar): ?>
                            <a href="home.php?id=<?php echo $id;?>"><img src="images/mini-<?php echo $gambar;?>" width="24" height="24" /></a>
                        <?php endforeach; ?>
                        </div>
                        
                        <div class="daftaricon round">
                        <p>Mengikuti</p>
                        <?php foreach($listfollowing as $id => $gambar): ?>
                            <a href="home.php?id=<?php echo $id;?>"><img src="images/mini-<?php echo $gambar;?>" width="24" height="24" /></a>
                        <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div id="footer" class="round">&copy;2010 for education purpose only <a href="http://saktidwicahyono.blogspot.com">Contact</a></div>
        </div>
    </body>
    <script type="text/javascript" src="js/jquery-1.4.2.min.js">
    </script>
    <script type="text/javascript">
        var polareply=/@.+$/;
        $(function(){
            var jmlkatatersisa=140;
            var isi=$('#isi'),counter=$('#counter'),
                tmbkirim=$('#tmbkirim'),
                daftartweet=$('#daftartweet');
                
            tmbkirim.attr("disabled","disabled");
            
            var gambar=$("#infouser > img").attr("src");
            
            console.log("username = "+nama);
            console.log("gambar = "+gambar);
            isi.keyup(function(){
                console.log("ada tombol ditekan isi = "+isi.val());
                console.log("jumlah karakter = "+isi.val().length);
                var jml=isi.val().length;
                console.log("Jumlah karakter tersisa = "+(jmlkatatersisa-jml));
                counter.html(jmlkatatersisa-jml);
                
                if(jml>0&&jml<=140){
                    tmbkirim.removeAttr("disabled");
                }else{
                    tmbkirim.attr("disabled","disabled");
                }
            });
            tmbkirim.click(function(){
                $.ajax({
                    url:"home.php",
                    data:"tweet=Tweet&op=ajax&isi="+isi.val(),
                    type:"POST",
                    dataType:"xml",
                    timeout:5000,
                    success:function(data){
                        console.log(data);
                        alert(data);
                        var tweet=$('tweet',data);
                        var berhasil=tweet.find('berhasil').text();
                        var nama=tweet.find('username').text();
                        var tweetisi=tweet.find('isi').text();
                        var tgl=tweet.find('tgl').text();
                        if(berhasil=="true"){
                            $('<li><a href="home.php?id='+nama+'"><img class="thumb" src="'+gambar+'" width="48" height="48"></a><span class="isitweet"><span class="usernm"><a href="home.php?id=">'+nama+'</a></span> '+tweetisi+'<div class="addinfo"><span class="tgl">'+tgl+'</span></div></span></li>').prependTo(daftartweet).hide().fadeIn('slow');
                            isi.val('');
                        }else{
                            alert("gagal ditambahkan");
                        }
                    },
                    error:function(){
                        alert("terjadi kesalahan");
                    }
                });
                return false;
            })
            tmbkirim.hover(function(){
                tmbkirim.addClass("tombolhover");
            },function(){
                tmbkirim.removeClass("tombolhover");
            });
            
        });
    </script>
</html>
