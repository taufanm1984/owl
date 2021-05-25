<?php //@Copy nangkoelframework
require_once('master_validation.php');
include_once('lib/nangkoelib.php');
include_once('lib/zLib.php');
#include_once('lib/zGrid.php');
#include_once('lib/rGrid.php');
include_once('lib/formTable.php');

$proses = $_GET['proses'];
$param = $_POST;

$lokasitugas=$_SESSION['empl']['lokasitugas'];

    $str="select * from ".$dbname.".bgt_regional_assignment 
        where kodeunit LIKE '".$_SESSION['empl']['lokasitugas']."%'
        ";
    $res=mysql_query($str);
    while($bar=mysql_fetch_object($res))
    {
        $regional=$bar->regional;
    }
//echo "error:".$regional; exit;
    // $str="select * from ".$dbname.".kebun_5kontrol
    //     where kode = 'proporsi6ha'
    //     ";
    // $res=mysql_query($str);
    // while($bar=mysql_fetch_object($res))
    // {
    //     $proporsi6ha=$bar->parameter;
    // }
    
//    echo "<pre>";
//    print_r($param);
//    echo "</pre>";

//dz: cek apakah inputan melebihi H+3 atau sesuai ketentuan (kebun_5kontrol)
$hplusberapa=0;
$skontrol="select parameter from ".$dbname.".kebun_5kontrol where kode = 'batashariinput' and kodeorg = '".$_SESSION['empl']['lokasitugas']."' ";
$qkontrol=mysql_query($skontrol) or die(mysql_error($conn));
while($rkontrol=mysql_fetch_assoc($qkontrol))
{
    $hplusberapa=$rkontrol['parameter'];
}        
// cek apakah hari batas merupakah hari libur
$bataslibur=false;
$tambahhari=0;
for ($i = 1; $i <= $hplusberapa; $i++) {
    $hariapabatas=date('D', strtotime('-'.$i.' days'));
    if($hariapabatas=='Sun')$tambahhari+=1;
    $strorg="select * from ".$dbname.".sdm_5harilibur where tanggal = '".date('Y-m-d', strtotime('-'.$i.' days'))."'";
    $queorg=mysql_query($strorg) or die(mysql_error());
    while($roworg=mysql_fetch_assoc($queorg))
    {
        if($roworg['keterangan']=='libur')$tambahhari+=1;
    }
}
$hplusberapa+=$tambahhari;
// kebun_5kontrol
    
$query = "SELECT kodeorg,luasareaproduktif
    FROM ".$dbname.".`setup_blok`
    WHERE 1
    ";
$qDetail=mysql_query($query) or die(mysql_error($conn));
while($rDetail=mysql_fetch_assoc($qDetail))
{
    $kamusluas[$rDetail['kodeorg']]=$rDetail['luasareaproduktif'];
}            

        // apakah dinyalain?
        $bkmfinger=false;
        $str="select parameter 
        from ".$dbname.".kebun_5kontrol
        where kode = 'bkmfinger'
        ";        
        $res=mysql_query($str);
        while($bar=mysql_fetch_object($res)){
            if($bar->parameter=='1')$bkmfinger=true;
        }   

switch($proses) {
    case 'showDetail':
	#== Prep Tab
	$headFrame = array(
	    $_SESSION['lang']['prestasi'],
	    $_SESSION['lang']['absensi'],
	    $_SESSION['lang']['material']
	);
	$contentFrame = array();
    
	# Options
        $tanggalx=substr($param['notransaksi'],0,4).'-'.substr($param['notransaksi'],4,2).'-'.substr($param['notransaksi'],6,2);
	#============== KHT, KHL dan Kontrak ======================
	$whereKary = "lokasitugas='".$_SESSION['empl']['lokasitugas']."' and ".
	    "tipekaryawan in (2,3,4,6) and (tanggalkeluar = '0000-00-00' or tanggalkeluar > '".$tanggalx."')";
//        echo $whereKary.' '.$param['tanggal'];
	#============== KHT, KHL dan Kontrak ======================
	#$whereOrg = "kodeorganisasi='".$_SESSION['empl']['lokasitugas']."' and ";
	#$whereOrg .= "tipe='BLOK' and induk='".$param['afdeling']."'";
	$whereKeg = "kodeorg='".$_SESSION['org']['kodeorganisasi']."' and ";
	$whereKeg .= "kelompok='PNN'";
	
//	$optKary = makeOption($dbname,'datakaryawan','karyawanid,namakaryawan,subbagian',$whereKary,'5');
        // finger update
        $tanggalberapa=substr($param['notransaksi'],0,4).'-'.substr($param['notransaksi'],4,2).'-'.substr($param['notransaksi'],6,2);   
        $besok = date("Y-m-d",strtotime("+1 day", strtotime($tanggalberapa)));
    
        if((($_SESSION['empl']['lokasitugas']!='SOGE')and($_SESSION['empl']['lokasitugas']!='SENE'))or($bkmfinger==false)){
            $optKary = makeOption($dbname,'datakaryawan','karyawanid,namakaryawan,subbagian',$whereKary,'5');
        }else{
            $optKary[]='Silakan pilih... (86)';

            if($_SESSION['empl']['lokasitugas']=='SENE'){
                $str="select a.scan_date, d.lokasitugas, c.karyawanid, d.namakaryawan, d.tipekaryawan, d.kodejabatan, d.subbagian, e.namaorganisasi as namaorganisasi 
                from ".$dbname.".att_log a
                left join ".$dbname.".att_adaptor c on a.pin = c.pin and c.sn in (select sn from ".$dbname.".att_mesinpresensi where lokasitugas like '".$_SESSION['empl']['lokasitugas']."%')
                left join ".$dbname.".datakaryawan d on c.karyawanid = d.karyawanid
                left join ".$dbname.".organisasi e on d.subbagian = e.kodeorganisasi
                where a.scan_date between '".$tanggalberapa." 03:00:00' and '".$besok." 02:59:59' and a.lokasitugas like '".$_SESSION['empl']['lokasitugas']."%'
                    and a.sn in (select sn from ".$dbname.".att_mesinpresensi where lokasitugas like '".$_SESSION['empl']['lokasitugas']."%')
                    and d.tipekaryawan in('1','2','3','4','6') ".$whereKaryBukanPemanen2."
                order by d.subbagian, d.namakaryawan, a.scan_date
                ";                    
            }else{
                $str="select a.scan_date, b.lokasitugas, c.karyawanid, d.namakaryawan, d.tipekaryawan, d.kodejabatan, d.subbagian, e.namaorganisasi as namaorganisasi 
                from ".$dbname.".att_log a
                left join ".$dbname.".att_mesinpresensi b on a.sn = b.sn
                left join ".$dbname.".att_adaptor c on a.pin = c.pin and a.sn = c.sn
                left join ".$dbname.".datakaryawan d on c.karyawanid = d.karyawanid
                left join ".$dbname.".organisasi e on d.subbagian = e.kodeorganisasi
                where a.scan_date between '".$tanggalberapa." 03:00:00' and '".$besok." 02:59:59' and b.lokasitugas like '".$_SESSION['empl']['lokasitugas']."%'
                    and d.tipekaryawan in('1','2','3','4','6') ".$whereKaryBukanPemanen2."
                order by d.subbagian, d.namakaryawan, a.scan_date
                ";        
            }        
    //        echo "error:".$str;
            $res=mysql_query($str,$conn);
            while($bar=mysql_fetch_object($res)){
            if($bar->karyawanid!=''){ // 2017-10-01 00:00:00
                if(!isset($datajam[$bar->karyawanid]['masuk'])){
                    $datajam[$bar->karyawanid]['masuk']=$bar->scan_date;
                }
                @$durasijamqwe=round((strtotime($bar->scan_date) - strtotime($datajam[$bar->karyawanid]['masuk']))/(60*60),2);
                    $datajam[$bar->karyawanid]['keluar']=$bar->scan_date;
                    $datajam[$bar->karyawanid]['durasi']=$durasijamqwe;
                $listkaryawan[$bar->karyawanid]=$bar->namakaryawan.' - '.$bar->subbagian;//.' - '.$bar->kodejabatan;
            }                            
            }
            
    $qwe=date('D', strtotime($tanggalberapa));
    if($tanggalberapa=='2019-06-01')$qwe='Fri'; // ganti hari 2019 2019-06-07
    
    // tambahan basis jumat karena hujan, tatang 2020-01-10 by Manusia Planet
    $adabj=false;
    $str="select kode, kodeorg, nama, parameter, lastuser, lastupdate from ".$dbname.".kebun_5kontrol
        where kode = 'basisjumat' and kodeorg = '".$_SESSION['empl']['lokasitugas']."' and parameter = '".$tanggalberapa."'
        ";
    $res=mysql_query($str);
    while($bar=mysql_fetch_object($res)){
        if($bar->parameter==$tanggalberapa)$adabj=true;
    }
    if($adabj)$qwe='Fri';
    // end of tambahan basis jumat
    
    if($qwe=='Sun'){
        $jamkerjaminimal=3.5;
    }else if($qwe=='Fri'){
        $jamkerjaminimal=5;
    }else{
        $jamkerjaminimal=7;
    }       
            
    if(!empty($listkaryawan))foreach($listkaryawan as $key => $value){
        $bolehtampil=true;
        if($datajam[$key]['durasi']<$jamkerjaminimal)$bolehtampil=false;        
        if($bolehtampil==true)        
        $optKary[$key]=$value." : ".$datajam[$key]['durasi']." jam";
    }            
        }                
        // end of finger update        
	$optKeg = makeOption($dbname,'setup_kegiatan','kodekegiatan,namakegiatan',$whereKeg);
	#$optOrg = makeOption($dbname,'organisasi','kodeorganisasi,namaorganisasi',$whereOrg);
	$optOrg = getOrgBelow($dbname,$param['afdeling'],false,'blok');
	$optThTanam= makeOption($dbname,'setup_blok','kodeorg,tahuntanam',
	    "kodeorg='".end(array_reverse(array_keys($optOrg)))."'");
	$optBin = array('1'=>'Ya','0'=>'Tidak');
	$thTanam = $optThTanam[end(array_reverse(array_keys($optOrg)))];
	
	#=============================== Get UMR ==============================
	$firstKary = getFirstKey($optKary);
	$qUMR = selectQuery($dbname,'sdm_5gajipokok','sum(jumlah) as nilai',
	    "karyawanid=".$firstKary." and tahun=".date('Y')." and idkomponen in (1,31)");
	$Umr = fetchData($qUMR);
	#=============================== Get UMR ==============================
	
	#================ Prestasi =============================
	# Get Data
	$where = "notransaksi='".$param['notransaksi']."'";
	$cols = "nik,kodeorg,tahuntanam,norma,outputminimal,hasilkerja,hasilkerjakg,upahkerja,upahpenalty,upahpremi,premibasis,".
	    "penalti1,penalti2,penalti3,penalti4,penalti5,penalti6,penalti7,penalti8,penalti9,penalti10,rupiahpenalty,luaspanen";
	$query = selectQuery($dbname,'kebun_prestasi',$cols,$where);
//        echo "error:".$query; exit;
	$data = fetchData($query);
	$dataShow = $data;
	foreach($dataShow as $key=>$row) {
	    $dataShow[$key]['nik'] = $optKary[$row['nik']];
	    #$dataShow[$key]['kodekegiatan'] = $optKeg[$row['kodekegiatan']];
	    $dataShow[$key]['kodeorg'] = $optOrg[$row['kodeorg']];
	    #$dataShow[$key]['pekerjaanpremi'] = $optBin[$row['pekerjaanpremi']];
	}
    
        // cari hari
        $day = date('D', strtotime($tanggalx));
        if($tanggalx=='2019-06-01')$day='Fri'; // ganti hari 2019 2019-06-07
        
    // tambahan basis jumat karena hujan, tatang 2020-01-10 by Manusia Planet
    $adabj=false;
    $str="select kode, kodeorg, nama, parameter, lastuser, lastupdate from ".$dbname.".kebun_5kontrol
        where kode = 'basisjumat' and kodeorg = '".$_SESSION['empl']['lokasitugas']."' and parameter = '".$tanggalx."'
        ";
    $res=mysql_query($str);
    while($bar=mysql_fetch_object($res)){
        if($bar->parameter==$tanggalx)$adabj=true;
    }
    if($adabj)$day='Fri';
    // end of tambahan basis jumat         
        
        if($day=='Sun')$libur=true; else $libur=false;
        // kamus hari libur
        $strorg="select * from ".$dbname.".sdm_5harilibur where tanggal = '".$tanggalx."'";
        $queorg=mysql_query($strorg) or die(mysql_error());
        while($roworg=mysql_fetch_assoc($queorg))
        {
//            $libur=true;
            if($roworg['keterangan']=='libur')$libur=true;
            if($roworg['keterangan']=='masuk')$libur=false;
        }        
        
	# Form
	$theForm2 = new uForm('prestasiForm','Form Prestasi',3);       
	$theForm2->addEls('nik',$_SESSION['lang']['nik'],'','select','L',25,$optKary);
        if($libur==false){
            // if($lokasitugas=='SOGE' or $lokasitugas=='SENE') perlakuan khusus SOGE SENE per 2017-05-22 tatang
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE')$theForm2->_elements[0]->_attr['onchange'] = "updUpah2()"; else
//            if($regional!='KALTIM')$theForm2->_elements[0]->_attr['onchange'] = "updUpah()"; else
 //           $theForm2->_elements[0]->_attr['onchange'] = "updUpah2()";
        }else{
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE')$theForm2->_elements[0]->_attr['onchange'] = "updUpah2()"; else
//            if($regional!='KALTIM')$theForm2->_elements[0]->_attr['onchange'] = "updUpah()"; else
 //           $theForm2->_elements[0]->_attr['onchange'] = "updUpah2()";
        }
	$theForm2->addEls('kodeorg',$_SESSION['lang']['kodeorg'],'','select','L',25,$optOrg);
        if($libur==false){
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE')$theForm2->_elements[1]->_attr['onchange'] = "updTahunTanam2();"; else
//            if($regional!='KALTIM')$theForm2->_elements[1]->_attr['onchange'] = "updTahunTanam();"; else
//            $theForm2->_elements[1]->_attr['onchange'] = "updTahunTanam2();";  
        }else{
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE')$theForm2->_elements[1]->_attr['onchange'] = "updTahunTanam2();"; else
//            if($regional!='KALTIM')$theForm2->_elements[1]->_attr['onchange'] = "updTahunTanam();"; else
//            $theForm2->_elements[1]->_attr['onchange'] = "updTahunTanam2();";  
        } 
//            $theForm2->_elements[1]->_attr['onchange'] = "updTahunTanam2();";  
        $theForm2->addEls('tahuntanam',$_SESSION['lang']['tahuntanam'],$thTanam,'textnum','R',6);
	$theForm2->_elements[2]->_attr['disabled'] = 'disabled';
//        $theForm2->addEls('bjr',$_SESSION['lang']['bjr'],'','textnum','R',6);
//	$theForm2->_elements[3]->_attr['disabled'] = 'disabled';
//        $theForm2->_elements[3]->_attr['onchange'] = "updBjr();";         
	$theForm2->addEls('norma',$_SESSION['lang']['basisjjg'],'0','textnum','R',10);
        if($libur==false){
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[3]->_attr['disabled'] = 'disabled';
        }else{
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[3]->_attr['disabled'] = 'disabled';
        }
//            $theForm2->_elements[3]->_attr['disabled'] = 'disabled';
	$theForm2->_elements[3]->_attr['title'] = 'Basis diambil dari tabel berdasarkan BJR';
	$theForm2->addEls('outputminimal',$_SESSION['lang']['outputminimal'],'0','textnum','R',10);
        if($libur==false){
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[4]->_attr['disabled'] = 'disabled';
        }else{
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[4]->_attr['disabled'] = 'disabled';
        }
//            $theForm2->_elements[4]->_attr['disabled'] = 'disabled';
	$theForm2->_elements[4]->_attr['title'] = 'Output minimal';
	$theForm2->addEls('hasilkerja',$_SESSION['lang']['hasilkerja'],'0','textnum','R',10);
//        if($libur==false){
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[5]->_attr['onblur'] = "updBjr();";     
            
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE')$theForm2->_elements[5]->_attr['onblur'] = "updBjr2();"; else
//            if($regional!='KALTIM')$theForm2->_elements[5]->_attr['onkeyup'] = "disablesimpan(this);"; else       
//            $theForm2->_elements[5]->_attr['onblur'] = "updBjr2();";    
//        }else{
//            $theForm2->_elements[5]->_attr['onblur'] = "updBjr3();"; 
//        }
//            $theForm2->_elements[5]->_attr['onblur'] = "updBjr3();"; 
	$theForm2->addEls('hasilkerjakg',$_SESSION['lang']['hasilkerjakg'],'0','textnum','R',10);
//	$theForm2->_elements[6]->_attr['disabled'] = 'disabled';
	$theForm2->_elements[6]->_attr['title'] = 'Hasil Kerja (JJG) * BJR bulan lalu';
        if($libur==false){
// sebelumnya ini defaulnya jadi Nol    $theForm2->addEls('upahkerja',$_SESSION['lang']['upahkerja'],$Umr[0]['nilai']/25,'textnum','R',10);
	    $theForm2->addEls('upahkerja',$_SESSION['lang']['upahkerja'],'0','textnum','R',10);
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[7]->_attr['disabled'] = 'disabled';
        }else{
            $theForm2->addEls('upahkerja',$_SESSION['lang']['upahkerja'],'0','textnum','R',10);
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[7]->_attr['disabled'] = 'disabled';
        }
	$theForm2->_elements[7]->_attr['title'] = 'Upah harian';
	$theForm2->addEls('upahpenalty',$_SESSION['lang']['upahpenalty'],'0','textnum','R',10);
        if($libur==false){
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[8]->_attr['disabled'] = 'disabled';
        }else{
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[8]->_attr['disabled'] = 'disabled';
        }
//            $theForm2->_elements[8]->_attr['disabled'] = 'disabled';
	$theForm2->_elements[8]->_attr['title'] = 'Denda upah harian';
	$theForm2->addEls('upahpremi',$_SESSION['lang']['premilebihbasis'],'0','textnum','R',10);
        if($libur==false){
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[9]->_attr['disabled'] = 'disabled';
        }else{
//           $theForm2->_elements[9]->_attr['disabled'] = 'disabled';
        } 
//            $theForm2->_elements[9]->_attr['disabled'] = 'disabled';
	$theForm2->_elements[9]->_attr['title'] = 'Hasil Kerja > Basis * Premi Lebih Basis';
	$theForm2->addEls('premibasis',$_SESSION['lang']['premibasis'],'0','textnum','R',10);
//        if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//	if($regional!='KALTIM')$theForm2->_elements[10]->_attr['disabled'] = 'disabled';
	$theForm2->_elements[10]->_attr['title'] = 'Premi Basis';
	/*$theForm2->addEls('umr',$_SESSION['lang']['umr'],'0','textnum','R',10);
	$theForm2->addEls('statusblok',$_SESSION['lang']['statusblok'],'-','text','L',4);
	$theForm2->addEls('pekerjaanpremi',$_SESSION['lang']['pekerjaanpremi'],'0','select','R',10,$optBin);*/
	$theForm2->addEls('penalti1',$_SESSION['lang']['penalti1'],'0','textnum','R',10);
	$theForm2->_elements[11]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('penalti2',$_SESSION['lang']['penalti2'],'0','textnum','R',10);
	$theForm2->_elements[12]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('penalti3',$_SESSION['lang']['penalti3'],'0','textnum','R',10);
	$theForm2->_elements[13]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('penalti4',$_SESSION['lang']['penalti4'],'0','textnum','R',10);
	$theForm2->_elements[14]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('penalti5',$_SESSION['lang']['penalti5'],'0','textnum','R',10);
	$theForm2->_elements[15]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('penalti6',$_SESSION['lang']['penalti6'],'0','textnum','R',10);
	$theForm2->_elements[16]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('penalti7',$_SESSION['lang']['penalti7'],'0','textnum','R',10);
	$theForm2->_elements[17]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('penalti8',$_SESSION['lang']['penalti8'],'0','textnum','R',10);
	$theForm2->_elements[18]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('penalti9',$_SESSION['lang']['penalti9'],'0','textnum','R',10);
	$theForm2->_elements[19]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('penalti10',$_SESSION['lang']['penalti10'],'0','textnum','R',10);
	$theForm2->_elements[20]->_attr['onchange'] = "updPenaltian()";
	$theForm2->addEls('rupiahpenalty',$_SESSION['lang']['rupiahpenalty'],'0','textnum','R',10);
//	$theForm2->_elements[21]->_attr['disabled'] = 'disabled';
	$theForm2->_elements[21]->_attr['title'] = 'Rupiah Penalty';
	$theForm2->addEls('luaspanen',$_SESSION['lang']['luaspanen'],'0','textnum','R',10);
 //       if($libur==false){
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){} else
//            if($regional!='KALTIM')$theForm2->_elements[22]->_attr['onblur'] = "updBjr();";     
            
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE')$theForm2->_elements[5]->_attr['onblur'] = "updBjr2();"; else
//            if($regional!='KALTIM')$theForm2->_elements[22]->_attr['onkeyup'] = "disablesimpan(this);"; else       
//            $theForm2->_elements[22]->_attr['onblur'] = "updBjr2();";    
//        }else{
//            $theForm2->_elements[22]->_attr['onblur'] = "updBjr3();"; 
//        }        
        
        // atas, tadinya ini... dipindah ke updBjr aja soale sama2 jadi faktor penentu 2018-11-13
//        if($libur==false){
////            if($lokasitugas=='SOGE' or $lokasitugas=='SENE')$theForm2->_elements[22]->_attr['onblur'] = "updUpah2()"; else
//            if($regional!='KALTIM')$theForm2->_elements[22]->_attr['onblur'] = "updUpah()"; else
//            $theForm2->_elements[22]->_attr['onblur'] = "updUpah2()";
//        }
	
	# Table
	$theTable2 = new uTable('prestasiTable','Tabel Prestasi',$cols,$data,$dataShow);
	
	# FormTable
	$formTab2 = new uFormTable('ftPrestasi',$theForm2,$theTable2,null,array('notransaksi'));
	$formTab2->_target = "kebun_slave_panen_detail";
	$formTab2->_noClearField = '##kodeorg##tahuntanam';
        if($libur==false){
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE')$formTab2->_noEnable = '##tahuntanam##rupiahpenalty'; else
            if($regional!='KALTIM')$formTab2->_noEnable = '##tahuntanam##norma##outputminimal##hasilkerjakg##upahkerja##upahpenalty##upahpremi##premibasis##rupiahpenalty'; else
            $formTab2->_noEnable = '##tahuntanam##rupiahpenalty';
            $formTab2->_defValue = '##upahkerja='.$Umr[0]['nilai']/25;
        }else{
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE')$formTab2->_noEnable = '##tahuntanam##outputminimal##hasilkerjakg##upahkerja##upahpenalty##premibasis##rupiahpenalty'; else
            if($regional!='KALTIM')$formTab2->_noEnable = '##tahuntanam##norma##outputminimal##hasilkerjakg##upahkerja##upahpenalty##premibasis##rupiahpenalty'; else
            $formTab2->_noEnable = '##tahuntanam##outputminimal##hasilkerjakg##upahkerja##upahpenalty##premibasis##rupiahpenalty';
        }
            
	$formTab2->_defValue = '##upahkerja=0';
	
	#== Display View
	# Draw Tab
	echo "<fieldset><legend><b>Detail</b></legend>";
       # echo "<button class=mybutton id=filternik onclick=filterKaryawan(val='null') title='Tampilkan Semua Karyawan'>Show All</button>";
	$formTab2->render();
	echo "</fieldset>";
	break;
    case 'add':
//        if($tanggal<'20140201'){ // sebelum tanggal 1 FEB 2014
//
//        }else{
            // cek yang bisa panen berdasarkan taksasi
            $luastaksasi=0;
            $hktaksasi=0;
            $query = "SELECT *
                FROM ".$dbname.".`kebun_taksasi` a
                WHERE a.`tanggal` = '".substr($param['notransaksi'],0,8)."' and a.`blok` = '".$param['kodeorg']."' and `posting` = 1
                ";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $luastaksasi=($rDetail['hasisa']+$rDetail['haesok']);
//                $hktaksasi=$rDetail['hkdigunakan'];
                $jjgmasak=$rDetail['jjgmasak'];
                $jjgoutput=$rDetail['hkdigunakan'];
            }
            
    $sorg="select kodeorg, jumlahpokok as pokokthnini, luasareaproduktif as hathnini from ".$dbname.".setup_blok where kodeorg ='".$param['kodeorg']."'";
    $qorg=mysql_query($sorg) or die(mysql_error($conn));
    while($rorg=mysql_fetch_assoc($qorg)){
        $pokok=$rorg['pokokthnini'];
        $luas=$rorg['hathnini'];
    }
    @$sph=round($pokok/$luas);                    
    
       $basis=0;
        // cek bjr via SETUP
        $query = "SELECT bjr, basis, premibasis, premilebihbasis
            FROM ".$dbname.".`kebun_5bjr` a
            WHERE a.`tahunproduksi` = '".substr($param['notransaksi'],0,4)."' and a.`kodeorg` = '".$param['kodeorg']."'
            ";
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $bjr=$rDetail['bjr'];
            $basis=$rDetail['basis'];
            $premibasis=$rDetail['premibasis'];            
            $premilebihbasis=$rDetail['premilebihbasis'];            
        }                
            
            @$hktaksasi=ceil($jjgmasak/$jjgoutput);

            $yangbisapanen=0;
            @$luasperhk=ceil($luastaksasi/$hktaksasi);
            if($luasperhk<=6){
                $yangbisapanen=$hktaksasi;            
            }else{
                $yangbisapanen=$luasperhk;
            }
            
    @$batasakp=($basis*2)/($sph*4)*100;     

            // cek hk panen 
//	$qUMR = selectQuery($dbname,'sdm_5gajipokok','sum(jumlah) as nilai',
//	    "karyawanid=".$firstKary." and tahun=".$param['tahun']." and idkomponen in (1,31)");            
            // kamus gaji pokok
            $query = "SELECT karyawanid, sum(jumlah) as nilai
                FROM ".$dbname.".`sdm_5gajipokok`
                WHERE tahun = '".substr($param['notransaksi'],0,4)."' and idkomponen in (1,31)
                ";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $kamusgajipokokharian[$rDetail['karyawanid']]=$rDetail['nilai'];
            }
            
            $hkpanen=0;
//            $query = "SELECT count(*) as hkpanen
//                FROM ".$dbname.".`kebun_prestasi_vw`
//                WHERE `tanggal` = '".substr($param['notransaksi'],0,8)."' and `kodeorg` like '".$param['kodeorg']."'
//                ";
            $query = "SELECT karyawanid, upahkerja
                FROM ".$dbname.".`kebun_prestasi_vw`
                WHERE `tanggal` = '".substr($param['notransaksi'],0,8)."' and `kodeorg` like '".$param['kodeorg']."'
                ";

            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
//                $hkpanen=$rDetail['hkpanen'];
                @$tambahanhk=$rDetail['upahkerja']/$kamusgajipokokharian[$rDetail['karyawanid']];
                $hkpanen+=$tambahanhk;
            }
            // end cek hk panen 
            
            // remove temp 20200915
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){
//                // tambahan tatang
//                $luasoutput=$luastaksasi/$hktaksasi;
////                echo "error:".$luasoutput."=".$luastaksasi."/".$hktaksasi."\n\n";
////                if($luasoutput<4){ // akp tinggi
//                if($akp>$batasakp){ // akp tinggi
//                    @$yangbisapanen=($luastaksasi*$sph*0.6)/(2*$basis);
////                    echo "error:".$yangbisapanen."=(".$luastaksasi."*".$sph."*0.6)/(2*".$basis.")\n\n";
//                    $statusakp="AKP tinggi";
//                }else{ // akp rendah
//                    @$yangbisapanen=$luas/4;
////                    echo "error:".$yangbisapanen."=".$luas."/4\n\n";
//                    $statusakp="AKP rendah";
//                }
//                $yangbisapanen=ceil($yangbisapanen);
//            }            
            
        // cari hari
        $day = date('D', strtotime(substr($param['notransaksi'],0,8)));
        if($day=='20190601')$day='Fri'; // ganti hari 2019 2019-06-07
        
    // tambahan basis jumat karena hujan, tatang 2020-01-10 by Manusia Planet
    $tanggalbj=substr($param['notransaksi'],0,4).'-'.substr($param['notransaksi'],4,2).'-'.substr($param['notransaksi'],6,2);
    $adabj=false;
    $str="select kode, kodeorg, nama, parameter, lastuser, lastupdate from ".$dbname.".kebun_5kontrol
        where kode = 'basisjumat' and kodeorg = '".$_SESSION['empl']['lokasitugas']."' and parameter = '".$tanggalbj."'
        ";
    $res=mysql_query($str);
    while($bar=mysql_fetch_object($res)){
        if($bar->parameter==$tanggalbj)$adabj=true;
    }
    if($adabj)$day='Fri';
    // end of tambahan basis jumat             
        
        if($day=='Sun')$libur=true; else $libur=false;
        // kamus hari libur
        $strorg="select * from ".$dbname.".sdm_5harilibur where tanggal = '".substr($param['notransaksi'],0,8)."'";
        $queorg=mysql_query($strorg) or die(mysql_error());
        while($roworg=mysql_fetch_assoc($queorg))
        {
//            $libur=true;
            if($roworg['keterangan']=='libur')$libur=true;
            if($roworg['keterangan']=='masuk')$libur=false;
        }        

            if($libur==false){
                if($regional!='KALTIM')if($hkpanen>$yangbisapanen){
                    echo "error: HK panen tidak boleh melebihi HK taksasi.\n
                        HK Taksasi: ".$yangbisapanen." (".$statusakp."), HK Panen: ".$hkpanen;
                    exit;
                }            
            }
//        }
//            echo "error:".$yangbisapanen."__".$hkpanen;
//            exit();
        
	$cols = array(
	    'nik','kodeorg','tahuntanam','norma','outputminimal','hasilkerja','hasilkerjakg','upahkerja','upahpenalty','upahpremi','premibasis',
	    'penalti1','penalti2','penalti3','penalti4','penalti5','penalti6','penalti7','penalti8','penalti9','penalti10',
	    'rupiahpenalty','luaspanen','notransaksi','kodekegiatan','statusblok','pekerjaanpremi'
	);
	$data = $param;
	unset($data['numRow']);
	# Additional Default Data
	$data['kodekegiatan'] = '0';
	$data['statusblok'] = 0;$data['pekerjaanpremi'] = 0;
        if($data['luaspanen']==0){
            $warning="Luas Panen(Ha)";
            echo "error: Silakan mengisi ".$warning.".";
            exit();
        }
        # periksa luas panen hari ini apakah sudah melebihi setup blok
//        // cari luas blok
//        $query = "SELECT luasareaproduktif
//            FROM ".$dbname.".`setup_blok`
//            WHERE `kodeorg` = '".$param['kodeorg']."'
//            ";
////        echo "error:".$query; exit;
//        $qDetail=mysql_query($query) or die(mysql_error($conn));
//        while($rDetail=mysql_fetch_assoc($qDetail))
//        {
//            $luasbloknya=$rDetail['luasareaproduktif'];
//        }      
        $luasbloknya=$kamusluas[$param['kodeorg']];

        // cari tanggal
        $query = "SELECT distinct tanggal
            FROM ".$dbname.".`kebun_aktifitas`
            WHERE `notransaksi` = '".$param['notransaksi']."'
            ";
//        echo "error:".$query; exit;
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $tanggalnya=$rDetail['tanggal'];
        }
        
        // kalo nilai disetting dan lebih batas, errorin
        if(($hplusberapa>0)and($regional!='KALTIM')){ // kalo kaltim lepasin limit
//            if($bataslibur)$hplusberapa+=1; // kalo hari batasnya libur, tambahin 1 hari
            // kalo gemuruh (WKNE03) tambahin 1 hari
            $haribatas=date('Y-m-d', strtotime('-'.$hplusberapa.' days'));
            $haribatas2=date('d-m-Y', strtotime('-'.$hplusberapa.' days'));
            
            if($tanggalnya<$haribatas){
                echo "Error : Batas tanggal input (".tanggalnormal($tanggalnya).") melebihi kebijakan (H-".$hplusberapa.") ".$haribatas2.".\nSilakan hubungi supervisor Agronomi/Akunting.";
                exit;
            }
        }
        //end of dz: cek apakah inputan melebihi H+3         
        
        // cari luas panen yang sudah diinput ditambah inputan
//        $luaspanennya=$data['luaspanen'];
        $query = "SELECT sum(luaspanen) as luaspanen
            FROM ".$dbname.".`kebun_prestasi_vw`
            WHERE `tanggal` = '".$tanggalnya."' and `kodeorg` ='".$param['kodeorg']."' and karyawanid != '".$param['nik']."'
            ";
//        echo "error:".$query; exit;
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $luaspanennya=$rDetail['luaspanen'];
        }
        $luaspanennya+=$param['luaspanen'];        
        
        // cari luas panen orang
        $query = "SELECT sum(luaspanen) as luaspanen
            FROM ".$dbname.".`kebun_prestasi_vw`
            WHERE `tanggal` = '".$tanggalnya."' and `karyawanid` ='".$param['nik']."'
            ";
//        echo "error:".$query; exit;
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $luaspanenorang=$rDetail['luaspanen'];
        }
        $luaspanenorang+=$data['luaspanen'];

        if($luaspanennya>$luasbloknya){
            $warning="Luas Panen ".$luaspanennya." melebihi Luas Blok ".$luasbloknya." (Ha)";
            echo "error: ".$warning.".";
            exit();               
        }else{

        }
        
        $query = insertQuery($dbname,'kebun_prestasi',$data,$cols);
        if(!mysql_query($query)) {
            echo "DB Error : ".mysql_error();
            exit;
        }
        unset($data['notransaksi']);unset($data['kodekegiatan']);
        unset($data['statusblok']);
        unset($data['pekerjaanpremi']);

//        $res = "";
//        foreach($data as $cont) {
//            $res .= "##".$cont;
//        }
//
//        $result = "{res:\"".$res."\",theme:\"".$_SESSION['theme']."\"}";
//        echo $result;
        
        // instruksi pak tatang, ini diremove 2018-11-26
//        if($libur==false){ // janjang max 1.1 x taksasi, kalo ga premi basisnya 1x
//            if($regional!='KALTIM'){
//                // ambil premi basis
////                $query = "SELECT afdeling, basis, premibasis, premilebihbasis
////                    FROM ".$dbname.".`kebun_5basispanen2`
////                    WHERE afdeling LIKE '".substr($data['kodeorg'],0,6)."' limit 1
////                    ";
////                $res = fetchData($query);
////                if(!empty($res)) {
////                    $premibasis=$res[0]['premibasis'];            
////                }
//                // ganti ini per 10 feb 2017 tatang
//                $query = "SELECT kodeorg, basis, premibasis, premilebihbasis
//                    FROM ".$dbname.".`kebun_5bjr`
//                    WHERE kodeorg = '".$param['kodeorg']."' and tahunproduksi = '".substr($param['notransaksi'],0,4)."' limit 1
//                    ";
//                $res = fetchData($query);
//                if(!empty($res)) {
//                    $premibasis=$res[0]['premibasis'];            
//                }
//
//                // cek janjang taksasi
//                $jjgmasak=0;
//                $query = "SELECT *
//                    FROM ".$dbname.".`kebun_taksasi` a
//                    WHERE a.`tanggal` = '".$tanggalnya."' and a.`blok` = '".$param['kodeorg']."' and `posting` = 1 
//                    ";
//                $qDetail=mysql_query($query) or die(mysql_error($conn));
//                while($rDetail=mysql_fetch_assoc($qDetail))
//                {
//                    $jjgmasak=$rDetail['jjgmasak'];
//                }
//
//                // cek janjang panen
//                $hasilkerja=0;
//                $query = "SELECT sum(hasilkerja) as hasilkerja
//                    FROM ".$dbname.".`kebun_prestasi_vw`
//                    WHERE `tanggal` = '".$tanggalnya."' and `kodeorg` ='".$param['kodeorg']."'
//                    ";
//                $qDetail=mysql_query($query) or die(mysql_error($conn));
//                while($rDetail=mysql_fetch_assoc($qDetail))
//                {
//                    $hasilkerja=$rDetail['hasilkerja'];
//                }          
//                
//                // cari luas panen
//                
//
//                $jjgmasak=$jjgmasak*1.1;
//        //        echo "error:".$jjgmasak.",".$hasilkerja;
//
//                // kalo janjang panen>(janjang masak taksasi x 1.1), set premibasis=53000 where premibasis>53000 and notransaksi=notransaksi
//                if($hasilkerja>$jjgmasak){
//                    $query="UPDATE `".$dbname."`.`kebun_prestasi` SET `premibasis` = '".$premibasis."' 
//                        WHERE `notransaksi` = '".$param['notransaksi']."' and `kodeorg` ='".$param['kodeorg']."' AND `premibasis` > '".$premibasis."'";
//                    if(!mysql_query($query)) {
//                        echo "DB Error : ".mysql_error();
//                        exit;
//                    }
//                }             
//            }
//        }
        
        if(($regional!='KALTIM')and($libur==false)){ // kalo libur ga pake proporsi dz: 20170223
        // cek apakah dalam satu hari bekerja di dua blok (CADSHBDDB)
        // dz: 20150226
            
        $query = "SELECT a.notransaksi, a.nik, a.kodeorg, a.luaspanen,
            a.hasilkerja, a.norma, a.outputminimal, a.upahkerja, a.upahpenalty, a.upahpremi, a.premibasis FROM ".$dbname.".`kebun_prestasi` a
            LEFT JOIN ".$dbname.".`kebun_aktifitas` b on a.notransaksi = b.notransaksi
            WHERE a.`kodekegiatan` = 0 and b.`tanggal` = '".$tanggalnya."' and a.`nik` = '".$param['nik']."'
            ";
//        echo "</br>error:".$query; exit;
        $patokanhasilkerja=0;
        $patokannorma=0;
        $patokanupahkerja=$param['upahkerja'];
        $totalhasilkerja=0;
        $patokanblok='';
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        $kasuskhusus=false;
        $jumlahluaspanendalamsehari=0;
        $jumlahluaskalioutputminimaldalamsehari=0;
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            //dikomen karena dibuat manual , agar ftprestasi tidak berubah jika lebih satu blok per pemanen 25-5-2021 taufan
      //      $oprekblok[$rDetail['kodeorg']]=$rDetail['kodeorg'];
            // if($proporsi6ha==true){ // proporsi6ha = 1
                
            // }else{ // proporsi6ha = 0
            //     if($kamusluas[$rDetail['kodeorg']]<6)$kasuskhusus=true;
            // }            
//            if($rDetail['kodeorg']=='MRKE01A02A')$kasuskhusus=true;
            $oprek[$rDetail['kodeorg']]['kodeorg']=$rDetail['kodeorg'];
            $oprek[$rDetail['kodeorg']]['hasilkerja']=$rDetail['hasilkerja'];
            $oprek[$rDetail['kodeorg']]['norma']=$rDetail['norma'];
            $oprek[$rDetail['kodeorg']]['outputminimal']=$rDetail['outputminimal'];
            $oprek[$rDetail['kodeorg']]['upahkerja']=$rDetail['upahkerja'];
//            $oprek[$rDetail['kodeorg']]['upahpenalty']=$rDetail['upahpenalty'];
            $oprek[$rDetail['kodeorg']]['upahpremi']=$rDetail['upahpremi'];
            $oprek[$rDetail['kodeorg']]['hasilkerja']=$rDetail['hasilkerja'];
            $oprek[$rDetail['kodeorg']]['premibasis']=$rDetail['premibasis'];
            
            $oprek[$rDetail['kodeorg']]['notransaksi']=$rDetail['notransaksi'];
            
            if($rDetail['hasilkerja']>$patokanhasilkerja){
                $patokanhasilkerja=$rDetail['hasilkerja'];
                $patokannorma=$rDetail['norma'];   
//                $patokanoutputminimal=$rDetail['outputminimal'];
                $patokanblok=$rDetail['kodeorg'];
            }            
            $totalhasilkerja+=$rDetail['hasilkerja'];
            
            // patokan output minimal hitung berdasarkan luas panen (tatang 2016-09-20)
            $jumlahluaspanendalamsehari+=$rDetail['luaspanen'];
            $jumlahluaskalioutputminimaldalamsehari+=($rDetail['outputminimal']*$rDetail['luaspanen']);
            @$patokanoutputminimal=$jumlahluaskalioutputminimaldalamsehari/$jumlahluaspanendalamsehari;
//            echo "error:patoutmin(".$patokanoutputminimal.")=jumluaxoutmindalseh(".$jumlahluaskalioutputminimaldalamsehari.")/jumluapandalseh(".$jumlahluaspanendalamsehari.")\n";
            // patokan output minimal (end)            
        }    
        
            // cek yang bisa panen berdasarkan taksasi
            $luastaksasi=0;
            $hktaksasi=0;
            $query = "SELECT *
                FROM ".$dbname.".`kebun_taksasi` a
                WHERE a.`tanggal` = '".$tanggalnya."' and a.`blok` = '".$patokanblok."' and `posting` = 1
                ";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $luastaksasi=($rDetail['hasisa']+$rDetail['haesok']);
//                $hktaksasi=$rDetail['hkdigunakan'];
                $jjgmasak=$rDetail['jjgmasak'];
                $jjgoutput=$rDetail['jjgoutput'];
                $akp=$rDetail['persenbuahmatang'];
            }

            @$hktaksasi=ceil($jjgmasak/$jjgoutput);


                // tambahan tatang
//                $luasoutput=$luastaksasi/$hktaksasi;             
//                
//                echo "error: ".$luastaksasi."/".$hktaksasi; exit;
//                
//    $sorg="select kodeorg, jumlahpokok as pokokthnini, luasareaproduktif as hathnini from ".$dbname.".setup_blok where kodeorg ='".$patokanblok."'";
//    $qorg=mysql_query($sorg) or die(mysql_error($conn));
//    while($rorg=mysql_fetch_assoc($qorg)){
//        $pokok=$rorg['pokokthnini'];
//        $luas=$rorg['hathnini'];
//    }
//    @$sph=round($pokok/$luas);  
//    @$batasakp=($patokannorma*2)/($sph*4)*100;                                
//                
                    $patokannormaawal=$patokannorma;
////                    echo "error: patokannorma:".$patokannorma; exit;
//            // =================================================================    
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){
////                if($luasoutput<4){ // akp tinggi
//                if($akp>$batasakp){ // akp tinggi
//                    $patokanoutputminimal=round(2*$patokannorma);                    
//                    $patokannorma=round(2*$patokannorma);           
//                    $akape="AKP tinggi";
//                }else{ // akp rendah
//                    $akape="AKP rendah";                    
//                }                
//            }                
//            // =================================================================    
        
        if(count($oprekblok)>1){
//            echo "error: blok utama: ".$patokanblok." basis: ".$patokannorma." hasil: ".$totalhasilkerja." akp: ".$akp." batasakp: ".$batasakp." ".$akape."\n";
//            echo "error: sph: ".$sph." pokok: ".$pokok." luas: ".$luas." luaspanen: ".$luaspanenorang."\n";
            // kamus premi basis
//            $query = "SELECT afdeling, bjr, basis, premibasis, premilebihbasis
//                FROM ".$dbname.".`kebun_5basispanen2`
//                WHERE afdeling LIKE '".substr($param['kodeorg'],0,6)."%' 
//                ";
//            $qDetail=mysql_query($query) or die(mysql_error($conn));
//            while($rDetail=mysql_fetch_assoc($qDetail))
//            {
//                $kamuspremibasis[$rDetail['afdeling']][$rDetail['bjr']]=$rDetail['premibasis'];
//                $kamuspremilebihbasis[$rDetail['afdeling']][$rDetail['bjr']]=$rDetail['premilebihbasis'];
//                $bjrbjrbjr[$rDetail['bjr']]=$rDetail['bjr'];
//            }
//            $bjrpalingkecildipremi=min($bjrbjrbjr);
//            $bjrpalingbesardipremi=max($bjrbjrbjr);
            // pindah di bawah feb 10, 2017 tatang

            // kamus bjr
            $query = "SELECT kodeorg, bjr, basis, premibasis, premilebihbasis 
                FROM ".$dbname.".`kebun_5bjr`
                WHERE tahunproduksi = '".substr($tanggalnya,0,4)."' and kodeorg like '".substr($param['kodeorg'],0,4)."%'
                ORDER BY bjr";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $kamusbjr[$rDetail['kodeorg']]=$rDetail['bjr'];
                $bjrbjrbjr[$rDetail['bjr']]=$rDetail['bjr'];
                $kamusbasis[$rDetail['kodeorg']]=$rDetail['basis'];
                $kamuspremibasis[$rDetail['kodeorg']]=$rDetail['premibasis'];
                $kamuspremilebihbasis[$rDetail['kodeorg']]=$rDetail['premilebihbasis'];
            }
            
            // kamus akp
            $query = "SELECT blok, hasisa, haesok, jmlhpokok, persenbuahmatang
                FROM ".$dbname.".`kebun_taksasi`
                WHERE tanggal = '".$tanggalnya."' and blok like '".substr($param['kodeorg'],0,4)."%'";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $kamusakp[$rDetail['blok']]=$rDetail['persenbuahmatang'];
                @$kamussph[$rDetail['blok']]=$rDetail['jmlhpokok']/($rDetail['hasisa']+$rDetail['haesok']);
            }            
            foreach($oprekblok as $oblok){
                $totalakp+=$kamusakp[$oblok];
                $totalsph+=$kamussph[$oblok];
                $totalbasis+=$kamusbasis[$oblok];
                $totalpremibasis+=$kamuspremibasis[$oblok];
                $totalpremilebihbasis+=$kamuspremilebihbasis[$oblok];
            }
            $rataakp=$totalakp/count($oprekblok);
            $ratasph=$totalsph/count($oprekblok);
            $ratabasis=$totalbasis/count($oprekblok);
            $ratapremibasis=$totalpremibasis/count($oprekblok);
            $ratapremilebihbasis=$totalpremilebihbasis/count($oprekblok);
            // janjangminimal
            $janjangminimal=$ratasph*4*$rataakp/100;
            if($day=='Fri'){
                @$ratabasis=5/7*$ratabasis;
                @$janjangminimal=5/7*$janjangminimal;
            }
            $ratabasis=round($ratabasis);               
            $janjangminimal=round($janjangminimal);               
            
        $basisluas=4;
        if($day=='Fri'){
            $basisluas=5/7*$basisluas;
        }
            // ini yang lama
//            if($janjangminimal>(2*$ratabasis)){
//                $akape="AKP tinggi";
//            }else{
//                $akape="AKP rendah";
//            }
            $ratabatasakp=($ratabasis*2)/($ratasph*$basisluas)*100;
            if($rataakp>$ratabatasakp){
                $akape="AKP tinggi";
            }else{
                $akape="AKP rendah";
            }

            // remove temp 20200915
//if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){
//    if($akape=="AKP tinggi"){
//        $basisnyajadi=$ratabasis*2;
//    }else{ // AKP rendah
//        $basisnyajadi=$ratabasis;
//    }
//    
//    // ini ga usah karena masing2 udah di5/7kan
////    if($day=='Fri'){
////        $basisnyajadi=5/7*$basisnyajadi;
////    }
////    $basisnyajadi=round($basisnyajadi);    
//    
//    @$patokanupahpenalty=$patokanupahkerja-(($totalhasilkerja/$basisnyajadi)*$patokanupahkerja);
//    if($patokanupahpenalty>0){
//        $patokanpremibasis=0;
//        $patokanpremilebihbasis=0;
//    }else{
//        @$patokanpremibasis=floor($totalhasilkerja/$ratabasis)*$ratapremibasis;
//        @$patokanpremilebihbasis=($totalhasilkerja-$ratabasis)*$ratapremilebihbasis;        
//    }
//    
//    if($totalhasilkerja>=$janjangminimal){ // kalo udah dapet 4 ha, ga kena proporsi...
//        $patokanupahpenalty=0;
//    }    
//    $basiswaktunya=4;
//    if($day=='Fri'){
//        $basiswaktunya=5/7*$basiswaktunya;
//    }
//    if($rataakp>$ratabatasakp){ // akp tinggi
//        
//    }else{ // akp rendah
//        // tambahan tatang 2018-11-31, kalo luas ga dapet, proporsi berdasarkan hektar
//        if(($jumlahluaspanendalamsehari<$basiswaktunya)and($patokanupahpenalty<=0)){
////            echo "error: masuk sini"; exit;
//            @$patokanupahpenalty=($basiswaktunya-$jumlahluaspanendalamsehari)/$basiswaktunya*$patokanupahkerja;
//        }
//    }
//                
////            echo "error: janjangminimal:".$janjangminimal."\n";
////            echo "error: luaspanenorang:".$luaspanenorang."\n";
////            echo "error: rataakp:".$rataakp."\n";
////            echo "error: janjangminimal:".$janjangminimal."\n";
////            echo "error: patokanupahpenalty:".$patokanupahpenalty."\n";
////            echo "error: :".$patokanupahkerja." - ((".$totalhasilkerja."/".$basisnyajadi.") * ".$patokanupahkerja." \n";
////            exit;    
//        
//    foreach($oprekblok as $oblok){
//        if($patokanupahkerja>0)
//        @$oprek[$oblok]['hitungupahkerja']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$patokanupahkerja;
//        
////        if($totalhasilkerja<$basisnyajadi){ // ga dapet basis
//        if($patokanupahpenalty>0)
//            @$oprek[$oblok]['hitungupahpenalty']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$patokanupahpenalty;
////        }else{ // dapet basis
//        if($patokanpremibasis>0)
//            @$oprek[$oblok]['hitungpremibasis']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$patokanpremibasis;
//        if($patokanpremilebihbasis>0)
//            @$oprek[$oblok]['hitungpremi']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$patokanpremilebihbasis;
////        }
//    }
//    
//}else
    {
            // kalo hari jumat basisnya 5/7
            if($day=='Fri'){
                $patokannorma=5/7*$patokannorma;
            }
            $patokannorma=round($patokannorma);

            $totalpatokanhasilkerja=0;
            foreach($oprekblok as $oblok){
//                echo "error: ".$patokannorma."/".$oprek[$oblok]['norma']."*".$oprek[$oblok]['hasilkerja']."\n";
                @$oprek[$oblok]['hitunghasilkerja']=$patokannorma/$oprek[$oblok]['norma']*$oprek[$oblok]['hasilkerja'];
                @$oprek[$oblok]['hitungupahkerja']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$patokanupahkerja;
                $totalhitunghasilkerja+=$oprek[$oblok]['hitunghasilkerja'];
                $totalhitunghasilkerja2+=$oprek[$oblok]['hasilkerja'];
    //            @$oprek[$oblok]['upahpenalty']=(-1)*($oprek[$oblok]['hasilkerja']-$oprek[$oblok]['norma'])/$oprek[$oblok]['norma']*$patokanupahkerja;            
                @$oprek[$oblok]['hitunggajidummy']=$oprek[$oblok]['hasilkerja']/$oprek[$oblok]['norma']*$patokanupahkerja; 
                $totalhitunggajidummy+=$oprek[$oblok]['hitunggajidummy'];                    
//echo "error: blok|".$oblok." patokanupahkerja|".$patokanupahkerja."\n";            
            }

//            if($kamusbjr[$patokanblok]<$bjrpalingkecildipremi)$bjrnya=$bjrpalingkecildipremi; else
//                if($kamusbjr[$patokanblok]>$bjrpalingbesardipremi)$bjrnya=$bjrpalingbesardipremi; else
            // ga dipake lagi feb 10, 2017 tatang
                    $bjrnya=$kamusbjr[$patokanblok];

//            $totalpremi=($totalhitunghasilkerja-$patokannorma)*$kamuspremilebihbasis[substr($patokanblok,0,6)][$bjrnya];
//            $totalpremibasis=floor($totalhitunghasilkerja/$patokannorma)*$kamuspremibasis[substr($patokanblok,0,6)][$bjrnya];
                    // ganti di bawah ini, feb 10, 2017 tatang
//            $totalpremi=($totalhitunghasilkerja-$patokannormaawal)*$kamuspremilebihbasis[$patokanblok];
                    // ganti lagi oct 1, 2020, tatang as request from soge
            $totalpremi=($totalhitunghasilkerja2-$ratabasis)*$ratapremilebihbasis;
//            echo "error : totalpremi = (".$totalhitunghasilkerja." - ".$ratabasis.") x ".$ratapremilebihbasis; exit;
//            $totalpremibasis=floor($totalhitunghasilkerja/$patokannormaawal)*$kamuspremibasis[$patokanblok];
                    // ganti lagi oct 1, 2020, tatang as request from soge
            $totalpremibasis=floor($totalhitunghasilkerja2/$ratabasis)*$ratapremibasis;
//            echo "error".$patokanblok.":(".$totalhitunghasilkerja."/".$patokannormaawal.")*".$kamuspremibasis[$patokanblok]; exit;

            $selisihgaji=$patokanupahkerja-$totalhitunggajidummy;

            // error:115 160. 115 harusnya 191
//            echo "error:".$patokanupahkerja." ".$totalhitunggajidummy; exit;

            foreach($oprekblok as $oblok){
                if($totalhitunghasilkerja2<$ratabasis){ // total lebih kecil dari output minimal
    //                @$oprek[$oblok]['hitungupahpenalty']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$oprek[$oblok]['upahpenalty'];
                    @$oprek[$oblok]['hitungupahpenalty']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$selisihgaji;
                    if($kasuskhusus==true)$oprek[$oblok]['hitungupahpenalty']=0;
                }else{ // total dapat output minimal
                    $oprek[$oblok]['hitungupahpenalty']=0;
                }
                if($totalhitunghasilkerja2>=$ratabasis){
                    @$oprek[$oblok]['hitungpremi']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$totalpremi;
                    @$oprek[$oblok]['hitungpremibasis']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$totalpremibasis;
                }else{
                    $oprek[$oblok]['hitungpremi']=0;
                    $oprek[$oblok]['hitungpremibasis']=0;
                }          
            }    
}            

            foreach($oprekblok as $oblok){
                $query="UPDATE `".$dbname."`.`kebun_prestasi` SET `upahkerja` = '".round($oprek[$oblok]['hitungupahkerja'])."',
                    `upahpenalty` = '".round($oprek[$oblok]['hitungupahpenalty'])."', `upahpremi` = '".round($oprek[$oblok]['hitungpremi'])."',
                    `premibasis` = '".round($oprek[$oblok]['hitungpremibasis'])."'
                    WHERE `notransaksi` = '".$oprek[$oblok]['notransaksi']."' and `kodeorg` ='".$oblok."' and `kodekegiatan` ='0' and `nik` = '".$param['nik']."'";
//                echo "error:".$query; exit;
                if(!mysql_query($query)) {
                    echo "DB Error : ".mysql_error();
                    exit;
                }            
            }

            $data['upahkerja']=round($oprek[$param['kodeorg']]['hitungupahkerja']);        
            $data['upahpenalty']=round($oprek[$param['kodeorg']]['hitungupahpenalty']);        
            $data['upahpremi']=round($oprek[$param['kodeorg']]['hitungpremi']);        
            $data['premibasis']=round($oprek[$param['kodeorg']]['hitungpremibasis']);                   
        } 

        // end of CADSHBDDB
        }else{ // kalo libur, proporsi gaji n dendanya saja 2018-11-26
            $firstKary = $param['nik'];
            $qUMR = selectQuery($dbname,'sdm_5gajipokok','sum(jumlah) as nilai',
                "karyawanid='".$firstKary."' and tahun='".substr($param['notransaksi'],0,4)."' and idkomponen in (1,31)");
            $Umr = fetchData($qUMR);
            $upahharian=round($Umr[0]['nilai']/25);
                        
            $query = "SELECT a.notransaksi, a.nik, a.kodeorg, a.luaspanen,
                a.hasilkerja, a.norma, a.outputminimal, a.upahkerja, a.upahpenalty, a.upahpremi, a.premibasis FROM ".$dbname.".`kebun_prestasi` a
                LEFT JOIN ".$dbname.".`kebun_aktifitas` b on a.notransaksi = b.notransaksi
                WHERE a.`kodekegiatan` = 0 and b.`tanggal` = '".$tanggalnya."' and a.`nik` = '".$param['nik']."'
                ";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail)){
                $oprekblok[$rDetail['kodeorg']]=$rDetail['kodeorg'];
                $oprek[$rDetail['kodeorg']]['hasilkerja']=$rDetail['hasilkerja'];
                $totalhasilkerja+=$rDetail['hasilkerja'];
            }
            if(count($oprekblok)>1){
                foreach($oprekblok as $oblok){
                    @$oprek[$oblok]['hitungupahkerja']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$upahharian;
                }
                foreach($oprekblok as $oblok){
                    $query="UPDATE `".$dbname."`.`kebun_prestasi` SET `upahkerja` = '".round($oprek[$oblok]['hitungupahkerja'])."',
                        `upahpenalty` = '".round($oprek[$oblok]['hitungupahkerja'])."'
                        WHERE `notransaksi` = '".$oprek[$oblok]['notransaksi']."' and `kodeorg` ='".$oblok."' and `kodekegiatan` ='0' and `nik` = '".$param['nik']."'";
    //                echo "error:".$query; exit;
                    if(!mysql_query($query)) {
                        echo "DB Error : ".mysql_error();
                        exit;
                    }            
                }                                
            }            
        }
        

        
        $res = "";
        foreach($data as $cont) {
            $res .= "##".$cont;
        }

        $result = "{res:\"".$res."\",theme:\"".$_SESSION['theme']."\"}";
        echo $result;
        
        break;
    case 'edit':
	$data = $param;
//        echo "error: ".print_r($data); exit;
        
        // cek inputan luas
        if($data['luaspanen']==0){
            $warning="Luas Panen(Ha)";
            echo "error: Silakan mengisi ".$warning.".";
            exit();
        }
        
        # periksa luas panen hari ini apakah sudah melebihi setup blok
//        // cari luas blok
//        $query = "SELECT luasareaproduktif
//            FROM ".$dbname.".`setup_blok`
//            WHERE `kodeorg` = '".$param['kodeorg']."'
//            ";
////        echo "error:".$query; exit;
//        $qDetail=mysql_query($query) or die(mysql_error($conn));
//        while($rDetail=mysql_fetch_assoc($qDetail))
//        {
//            $luasbloknya=$rDetail['luasareaproduktif'];
//        }          
        $luasbloknya=$kamusluas[$param['kodeorg']];

        // cari tanggal
        $query = "SELECT distinct tanggal
            FROM ".$dbname.".`kebun_prestasi_vw`
            WHERE `notransaksi` = '".$param['notransaksi']."'
            ";
//        echo "error:".$query; exit;
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $tanggalnya=$rDetail['tanggal'];
        }
        
        // kalo nilai disetting dan lebih batas, errorin
        if(($hplusberapa>0)and($regional!='KALTIM')){ // kalo kaltim lepasin limit
//            if($bataslibur)$hplusberapa+=1; // kalo hari batasnya libur, tambahin 1 hari
            // kalo gemuruh (WKNE03) tambahin 1 hari
            $haribatas=date('Y-m-d', strtotime('-'.$hplusberapa.' days'));
            $haribatas2=date('d-m-Y', strtotime('-'.$hplusberapa.' days'));
            
            if($tanggalnya<$haribatas){
                echo "Error : Batas tanggal input (".tanggalnormal($tanggalnya).") melebihi kebijakan (H-".$hplusberapa.") ".$haribatas2.".\nSilakan hubungi supervisor Agronomi/Akunting.";
                exit;
            }
        }
        //end of dz: cek apakah inputan melebihi H+3         

        // cari luas panen yang sudah diinput ditambah inputan
//        $luaspanennya=$data['luaspanen'];
        $query = "SELECT sum(luaspanen) as luaspanen
            FROM ".$dbname.".`kebun_prestasi_vw`
            WHERE `tanggal` = '".$tanggalnya."' and `kodeorg` ='".$param['kodeorg']."' and karyawanid != '".$param['nik']."'
            ";
//        echo "error:".$query; exit;
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $luaspanennya=$rDetail['luaspanen'];
        }
        $luaspanennya+=$param['luaspanen'];

        // cari luas panen orang
        $query = "SELECT sum(luaspanen) as luaspanen
            FROM ".$dbname.".`kebun_prestasi_vw`
            WHERE `tanggal` = '".$tanggalnya."' and `karyawanid` ='".$param['nik']."' and kodeorg != '".$param['kodeorg']."'
            ";
//        echo "error:".$query; exit;
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $luaspanenorang=$rDetail['luaspanen'];
        }
        $luaspanenorang+=$data['luaspanen'];
        
        if($luaspanennya>$luasbloknya){
            $warning="Luas Panen ".$luaspanennya." melebihi Luas Blok ".$luasbloknya." (Ha)";
            echo "error: ".$warning.".";
            exit();               
        }else{

        }        
        
	unset($data['notransaksi']);
	foreach($data as $key=>$cont) {
	    if(substr($key,0,5)=='cond_') {
		unset($data[$key]);
	    }
	}
	$where = "notransaksi='".$param['notransaksi']."' and nik='".$param['cond_nik'].
	    "' and kodeorg='".$param['cond_kodeorg']."'";
	$query = updateQuery($dbname,'kebun_prestasi',$data,$where);
//        update owldb.kebun_prestasi 
//        set `nik`='0000002610',`kodeorg`='SOGE01D017',`tahuntanam`='2012',
//        `norma`='0',`outputminimal`='0',`hasilkerja`='237',`hasilkerjakg`='1820.16',
//        `upahkerja`='103840',`upahpenalty`='103840',`upahpremi`='248850',`premibasis`='0',
//        `penalti1`='0',`penalti2`='0',`penalti3`='0',`penalti4`='0',`penalti5`='0',`penalti6`='0',`penalti7`='0',`penalti8`='0',`penalti9`='0',`penalti10`='0',
//        `rupiahpenalty`='0',`luaspanen`='2.84' 
//        where notransaksi='20181125/SOGE/PNN/003' and nik='0000002610' and kodeorg='SOGE01D017'        
//        echo "error:".$query; exit;
	if(!mysql_query($query)) {
	    echo "DB Error : ".mysql_error();
	    exit;
	}
        
        // cari hari
        $day = date('D', strtotime($tanggalnya));
        if($tanggalnya=='2019-06-01')$day='Fri'; // ganti hari 2019 2019-06-07
        
    // tambahan basis jumat karena hujan, tatang 2020-01-10 by Manusia Planet
    $adabj=false;
    $str="select kode, kodeorg, nama, parameter, lastuser, lastupdate from ".$dbname.".kebun_5kontrol
        where kode = 'basisjumat' and kodeorg = '".$_SESSION['empl']['lokasitugas']."' and parameter = '".$tanggalnya."'
        ";
    $res=mysql_query($str);
    while($bar=mysql_fetch_object($res)){
        if($bar->parameter==$tanggalnya)$adabj=true;
    }
    if($adabj)$day='Fri';
    // end of tambahan basis jumat         
        
        if($day=='Sun')$libur=true; else $libur=false;
        // kamus hari libur
        $strorg="select * from ".$dbname.".sdm_5harilibur where tanggal = '".$tanggalnya."'";
        $queorg=mysql_query($strorg) or die(mysql_error());
        while($roworg=mysql_fetch_assoc($queorg))
        {
//            $libur=true;
            if($roworg['keterangan']=='libur')$libur=true;
            if($roworg['keterangan']=='masuk')$libur=false;
        }        
        
        // instruksi pak tatang, ini diremove 2018-11-26
//	echo json_encode($param);
//        if($libur==false){
//            if($regional!='KALTIM'){
//                // ambil premi basis
////                $query = "SELECT afdeling, basis, premibasis, premilebihbasis
////                    FROM ".$dbname.".`kebun_5basispanen2`
////                    WHERE afdeling LIKE '".substr($data['kodeorg'],0,6)."' limit 1
////                    ";
////                $res = fetchData($query);
////                if(!empty($res)) {
////                    $premibasis=$res[0]['premibasis'];            
////                }
//                // ganti ini per 10 feb 2017 tatang
//                $query = "SELECT kodeorg, basis, premibasis, premilebihbasis
//                    FROM ".$dbname.".`kebun_5bjr`
//                    WHERE kodeorg = '".$param['kodeorg']."' and tahunproduksi = '".substr($param['notransaksi'],0,4)."' limit 1
//                    ";
//                $res = fetchData($query);
//                if(!empty($res)) {
//                    $premibasis=$res[0]['premibasis'];            
//                }
//
//                // cek janjang taksasi
//                $jjgmasak=0;
//                $query = "SELECT *
//                    FROM ".$dbname.".`kebun_taksasi` a
//                    WHERE a.`tanggal` = '".$tanggalnya."' and a.`blok` = '".$param['kodeorg']."' and `posting` = 1
//                    ";
//                $qDetail=mysql_query($query) or die(mysql_error($conn));
//                while($rDetail=mysql_fetch_assoc($qDetail))
//                {
//                    $jjgmasak=$rDetail['jjgmasak'];
//                }
//
//                // cek janjang panen
//                $hasilkerja=0;
//                $query = "SELECT sum(hasilkerja) as hasilkerja
//                    FROM ".$dbname.".`kebun_prestasi_vw`
//                    WHERE `tanggal` = '".$tanggalnya."' and `kodeorg` ='".$param['kodeorg']."'
//                    ";
//                $qDetail=mysql_query($query) or die(mysql_error($conn));
//                while($rDetail=mysql_fetch_assoc($qDetail))
//                {
//                    $hasilkerja=$rDetail['hasilkerja'];
//                }          
//
//                $jjgmasak=$jjgmasak*1.1;
//        //        echo "error:".$jjgmasak.",".$hasilkerja;
//                
//                // janjang output di taksasi itu udah dikali 1.1
//                // kalo janjang panen>(janjang masak taksasi x 1.1), set premibasis=53000 where premibasis>53000 and notransaksi=notransaksi
//                if($hasilkerja>$jjgmasak){
//                    $query="UPDATE `".$dbname."`.`kebun_prestasi` SET `premibasis` = '".$premibasis."' 
//                        WHERE `notransaksi` = '".$param['notransaksi']."' and `kodeorg` ='".$param['kodeorg']."' AND `premibasis` > '".$premibasis."'";
//    //            echo "error:".$query;
//    //            exit;
//                    if(!mysql_query($query)) {
//                        echo "DB Error : ".mysql_error();
//                        exit;
//                    }
//                }                    
//            }            
//        }

//        echo "error: CEK APAKAH TOTAL MELEBIHI JANJANG TAKSASI?\n".
//            $param['kodeorg']." ".$tanggalnya." | premibasis:".$premibasis." jjgmasak(tak):".$jjgmasak." hasilkerja(pnn):".$hasilkerja."\n";
//        exit;   
        
//        echo "error: ".$regional." ".$libur; exit;
        
        if(($regional!='KALTIM')and($libur==false)){ // kalo libur ga pake proporsi dz: 20170223
        // cek apakah dalam satu hari bekerja di dua blok (CADSHBDDB)
        // dz: 20150226
            
        $query = "SELECT a.notransaksi, a.nik, a.kodeorg, a.luaspanen,
            a.hasilkerja, a.norma, a.outputminimal, a.upahkerja, a.upahpenalty, a.upahpremi, a.premibasis FROM ".$dbname.".`kebun_prestasi` a
            LEFT JOIN ".$dbname.".`kebun_aktifitas` b on a.notransaksi = b.notransaksi
            WHERE a.`kodekegiatan` = 0 and b.`tanggal` = '".$tanggalnya."' and a.`nik` = '".$param['nik']."'
            ";
//        echo "</br>error:".$query; exit;
        $patokanhasilkerja=0;
        $patokannorma=0;
        $patokanupahkerja=$param['upahkerja'];
        $totalhasilkerja=0;
        $patokanblok='';
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        $kasuskhusus=false;
        $jumlahluaspanendalamsehari=0;
        $jumlahluaskalioutputminimaldalamsehari=0;
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
      //      $oprekblok[$rDetail['kodeorg']]=$rDetail['kodeorg'];
            // if($proporsi6ha==true){ // proporsi6ha = 1
                
            // }else{ // proporsi6ha = 0
            //     if($kamusluas[$rDetail['kodeorg']]<6)$kasuskhusus=true;
            // }            
//            if($rDetail['kodeorg']=='MRKE01A02A')$kasuskhusus=true;
            $oprek[$rDetail['kodeorg']]['kodeorg']=$rDetail['kodeorg'];
            $oprek[$rDetail['kodeorg']]['hasilkerja']=$rDetail['hasilkerja'];
            $oprek[$rDetail['kodeorg']]['norma']=$rDetail['norma'];
            $oprek[$rDetail['kodeorg']]['outputminimal']=$rDetail['outputminimal'];
            $oprek[$rDetail['kodeorg']]['upahkerja']=$rDetail['upahkerja'];
//            $oprek[$rDetail['kodeorg']]['upahpenalty']=$rDetail['upahpenalty'];
            $oprek[$rDetail['kodeorg']]['upahpremi']=$rDetail['upahpremi'];
            $oprek[$rDetail['kodeorg']]['hasilkerja']=$rDetail['hasilkerja'];
            $oprek[$rDetail['kodeorg']]['premibasis']=$rDetail['premibasis'];
            
            $oprek[$rDetail['kodeorg']]['notransaksi']=$rDetail['notransaksi'];
            
            if($rDetail['hasilkerja']>$patokanhasilkerja){
                $patokanhasilkerja=$rDetail['hasilkerja'];
                $patokannorma=$rDetail['norma'];   
//                $patokanoutputminimal=$rDetail['outputminimal'];
                $patokanblok=$rDetail['kodeorg'];
            }            
            $totalhasilkerja+=$rDetail['hasilkerja'];
            
            // patokan output minimal hitung berdasarkan luas panen (tatang 2016-09-20)
            $jumlahluaspanendalamsehari+=$rDetail['luaspanen'];
            $jumlahluaskalioutputminimaldalamsehari+=($rDetail['outputminimal']*$rDetail['luaspanen']);
            @$patokanoutputminimal=$jumlahluaskalioutputminimaldalamsehari/$jumlahluaspanendalamsehari;
//            echo "error:patoutmin(".$patokanoutputminimal.")=jumluaxoutmindalseh(".$jumlahluaskalioutputminimaldalamsehari.")/jumluapandalseh(".$jumlahluaspanendalamsehari.")\n";
            // patokan output minimal (end)            
        }    
        
            // cek yang bisa panen berdasarkan taksasi
            $luastaksasi=0;
            $hktaksasi=0;
            $query = "SELECT *
                FROM ".$dbname.".`kebun_taksasi` a
                WHERE a.`tanggal` = '".$tanggalnya."' and a.`blok` = '".$patokanblok."' and `posting` = 1
                ";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $luastaksasi=($rDetail['hasisa']+$rDetail['haesok']);
//                $hktaksasi=$rDetail['hkdigunakan'];
                $jjgmasak=$rDetail['jjgmasak'];
                $jjgoutput=$rDetail['jjgoutput'];
                $akp=$rDetail['persenbuahmatang'];
            }

            @$hktaksasi=ceil($jjgmasak/$jjgoutput);


                // tambahan tatang
//                $luasoutput=$luastaksasi/$hktaksasi;             
//                
//                echo "error: ".$luastaksasi."/".$hktaksasi; exit;
//                
//    $sorg="select kodeorg, jumlahpokok as pokokthnini, luasareaproduktif as hathnini from ".$dbname.".setup_blok where kodeorg ='".$patokanblok."'";
//    $qorg=mysql_query($sorg) or die(mysql_error($conn));
//    while($rorg=mysql_fetch_assoc($qorg)){
//        $pokok=$rorg['pokokthnini'];
//        $luas=$rorg['hathnini'];
//    }
//    @$sph=round($pokok/$luas);  
//    @$batasakp=($patokannorma*2)/($sph*4)*100;                                
//                
                    $patokannormaawal=$patokannorma;
////                    echo "error: patokannorma:".$patokannorma; exit;
//            // =================================================================    
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){
////                if($luasoutput<4){ // akp tinggi
//                if($akp>$batasakp){ // akp tinggi
//                    $patokanoutputminimal=round(2*$patokannorma);                    
//                    $patokannorma=round(2*$patokannorma);           
//                    $akape="AKP tinggi";
//                }else{ // akp rendah
//                    $akape="AKP rendah";                    
//                }                
//            }                
//            // =================================================================    
        
        if(count($oprekblok)>1){
//            echo "error: blok utama: ".$patokanblok." basis: ".$patokannorma." hasil: ".$totalhasilkerja." akp: ".$akp." batasakp: ".$batasakp." ".$akape."\n";
//            echo "error: sph: ".$sph." pokok: ".$pokok." luas: ".$luas." luaspanen: ".$luaspanenorang."\n";
            // kamus premi basis
//            $query = "SELECT afdeling, bjr, basis, premibasis, premilebihbasis
//                FROM ".$dbname.".`kebun_5basispanen2`
//                WHERE afdeling LIKE '".substr($param['kodeorg'],0,6)."%' 
//                ";
//            $qDetail=mysql_query($query) or die(mysql_error($conn));
//            while($rDetail=mysql_fetch_assoc($qDetail))
//            {
//                $kamuspremibasis[$rDetail['afdeling']][$rDetail['bjr']]=$rDetail['premibasis'];
//                $kamuspremilebihbasis[$rDetail['afdeling']][$rDetail['bjr']]=$rDetail['premilebihbasis'];
//                $bjrbjrbjr[$rDetail['bjr']]=$rDetail['bjr'];
//            }
//            $bjrpalingkecildipremi=min($bjrbjrbjr);
//            $bjrpalingbesardipremi=max($bjrbjrbjr);
            // pindah di bawah feb 10, 2017 tatang

            // kamus bjr
            $query = "SELECT kodeorg, bjr, basis, premibasis, premilebihbasis 
                FROM ".$dbname.".`kebun_5bjr`
                WHERE tahunproduksi = '".substr($tanggalnya,0,4)."' and kodeorg like '".substr($param['kodeorg'],0,4)."%'
                ORDER BY bjr";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $kamusbjr[$rDetail['kodeorg']]=$rDetail['bjr'];
                $bjrbjrbjr[$rDetail['bjr']]=$rDetail['bjr'];
                $kamusbasis[$rDetail['kodeorg']]=$rDetail['basis'];
                $kamuspremibasis[$rDetail['kodeorg']]=$rDetail['premibasis'];
                $kamuspremilebihbasis[$rDetail['kodeorg']]=$rDetail['premilebihbasis'];
            }
            
            // kamus akp
            $query = "SELECT blok, hasisa, haesok, jmlhpokok, persenbuahmatang
                FROM ".$dbname.".`kebun_taksasi`
                WHERE tanggal = '".$tanggalnya."' and blok like '".substr($param['kodeorg'],0,4)."%'";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $kamusakp[$rDetail['blok']]=$rDetail['persenbuahmatang'];
                @$kamussph[$rDetail['blok']]=$rDetail['jmlhpokok']/($rDetail['hasisa']+$rDetail['haesok']);
            }            
            foreach($oprekblok as $oblok){
                $totalakp+=$kamusakp[$oblok];
                $totalsph+=$kamussph[$oblok];
                $totalbasis+=$kamusbasis[$oblok];
                $totalpremibasis+=$kamuspremibasis[$oblok];
                $totalpremilebihbasis+=$kamuspremilebihbasis[$oblok];
            }
            $rataakp=$totalakp/count($oprekblok);
            $ratasph=$totalsph/count($oprekblok);
            $ratabasis=$totalbasis/count($oprekblok);
            $ratapremibasis=$totalpremibasis/count($oprekblok);
            $ratapremilebihbasis=$totalpremilebihbasis/count($oprekblok);
            // janjangminimal
            $janjangminimal=$ratasph*4*$rataakp/100;
            if($day=='Fri'){
                @$ratabasis=5/7*$ratabasis;
                @$janjangminimal=5/7*$janjangminimal;
            }
            $ratabasis=round($ratabasis);               
            $janjangminimal=round($janjangminimal);               


        $basisluas=4;
        if($day=='Fri'){
            $basisluas=5/7*$basisluas;
        }
            // ini yang lama
//            if($janjangminimal>(2*$ratabasis)){
//                $akape="AKP tinggi";
//            }else{
//                $akape="AKP rendah";
//            }
            $ratabatasakp=($ratabasis*2)/($ratasph*$basisluas)*100;
            if($rataakp>$ratabatasakp){
                $akape="AKP tinggi";
            }else{
                $akape="AKP rendah";
            }

            // remove temp 20200915
//if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){
//    if($akape=="AKP tinggi"){
//        $basisnyajadi=$ratabasis*2;
//    }else{ // AKP rendah
//        $basisnyajadi=$ratabasis;
//    }
//    
//    // ini ga usah karena masing2 udah di5/7kan
////    if($day=='Fri'){
////        $basisnyajadi=5/7*$basisnyajadi;
////    }
////    $basisnyajadi=round($basisnyajadi);    
//    
//    @$patokanupahpenalty=$patokanupahkerja-(($totalhasilkerja/$basisnyajadi)*$patokanupahkerja); // proporsi upah tergantung akp tinggi ato rendah?
//    if($patokanupahpenalty>0){
//        $patokanpremibasis=0;
//        $patokanpremilebihbasis=0;
//    }else{
//        @$patokanpremibasis=floor($totalhasilkerja/$ratabasis)*$ratapremibasis;
//        @$patokanpremilebihbasis=($totalhasilkerja-$ratabasis)*$ratapremilebihbasis;        
//    }
////    echo "error: patokanpremilebihbasis:".$patokanpremilebihbasis." totalhasilkerja:".$totalhasilkerja." ratabasis:".$ratabasis." ratapremilebihbasis:".$ratapremilebihbasis; exit;
////    error: patokanpremilebihbasis:-7666.6666666667 totalhasilkerja:69 ratabasis:79 ratapremilebihbasis:766.66666666667
//    
//    if($totalhasilkerja>=$janjangminimal){ // kalo udah dapet 4 ha, ga kena proporsi...
//        $patokanupahpenalty=0;
//    }    
//    $basiswaktunya=4;
//    if($day=='Fri'){
//        $basiswaktunya=5/7*$basiswaktunya;
//    }
//    if($rataakp>$ratabatasakp){ // akp tinggi
//        
//    }else{ // akp rendah
//        // tambahan tatang 2018-11-31, kalo luas ga dapet, proporsi berdasarkan hektar
//        if(($jumlahluaspanendalamsehari<$basiswaktunya)and($patokanupahpenalty<=0)){
////            echo "error: masuk sini"; exit;
//            @$patokanupahpenalty=($basiswaktunya-$jumlahluaspanendalamsehari)/$basiswaktunya*$patokanupahkerja;
//        }
//    }
//                
////ERROR TRANSACTION,
////error: totalhasilkerja:55
////error: janjangminimal:91
////error: luaspanenorang:4
////error: rataakp:16
////error: janjangminimal:91
////error: patokanupahpenalty:56246.666666667
////error: :103840 - ((55/120) * 103840 
//        
////error: totalhasilkerja:76
////error: janjangminimal:124
////error: luaspanenorang:4
////error: rataakp:31
////error: janjangminimal:124
////error: patokanupahpenalty:30084.485981308
////error: patokanpremibasis:0
////error: patokanpremilebihbasis:-24800
////error: :103840 - ((76/107) * 103840   
//    
////            echo "error: totalhasilkerja:".$totalhasilkerja."\n";
////            echo "error: janjangminimal:".$janjangminimal."\n";
////            echo "error: luaspanenorang:".$luaspanenorang."\n";
////            echo "error: rataakp:".$rataakp."\n";
////            echo "error: akape:".$akape."\n";
////            echo "error: ratabatasakp:".$ratabatasakp."\n";
////            echo "error: janjangminimal:".$janjangminimal."\n";
////            echo "error: patokanupahpenalty:".$patokanupahpenalty."\n";
////            echo "error: patokanpremibasis:".$patokanpremibasis." totalhasilkerja(".$totalhasilkerja.")/ratabasis(".$ratabasis.")*ratapremibasis(".$ratapremibasis.") \n";
////            echo "error: patokanpremilebihbasis:".$patokanpremilebihbasis." (totalhasilkerja(".$totalhasilkerja.")-ratabasis(".$ratabasis."))*ratapremilebihbasis(".$ratapremilebihbasis.") \n";
////            echo "error: :".$patokanupahkerja." - ((".$totalhasilkerja."/".$basisnyajadi.") * ".$patokanupahkerja." \n";
////            exit;    
//        
//    foreach($oprekblok as $oblok){
//        if($patokanupahkerja>0)
//        @$oprek[$oblok]['hitungupahkerja']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$patokanupahkerja;
////        echo "error: ".$totalhasilkerja." ".$basisnyajadi; exit; // 55 120
//        
////        if($totalhasilkerja<$basisnyajadi){ // ga dapet basis
//        if($patokanupahpenalty>0)
//            @$oprek[$oblok]['hitungupahpenalty']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$patokanupahpenalty;
////        }else{ // dapet basis
//        if($patokanpremibasis>0)
//            @$oprek[$oblok]['hitungpremibasis']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$patokanpremibasis;
//        if($patokanpremilebihbasis>0)
//            @$oprek[$oblok]['hitungpremi']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$patokanpremilebihbasis;
////        }
//    }
//    
//}else
    {
            // kalo hari jumat basisnya 5/7
            if($day=='Fri'){
                $patokannorma=5/7*$patokannorma;
            }
            $patokannorma=round($patokannorma);

            $totalpatokanhasilkerja=0;
            foreach($oprekblok as $oblok){
//                echo "error: ".$patokannorma."/".$oprek[$oblok]['norma']."*".$oprek[$oblok]['hasilkerja']."\n"; exit;
                @$oprek[$oblok]['hitunghasilkerja']=$patokannorma/$oprek[$oblok]['norma']*$oprek[$oblok]['hasilkerja'];
                @$oprek[$oblok]['hitungupahkerja']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$patokanupahkerja;
                $totalhitunghasilkerja+=$oprek[$oblok]['hitunghasilkerja'];
                $totalhitunghasilkerja2+=$oprek[$oblok]['hasilkerja'];
    //            @$oprek[$oblok]['upahpenalty']=(-1)*($oprek[$oblok]['hasilkerja']-$oprek[$oblok]['norma'])/$oprek[$oblok]['norma']*$patokanupahkerja;            
                @$oprek[$oblok]['hitunggajidummy']=$oprek[$oblok]['hasilkerja']/$oprek[$oblok]['norma']*$patokanupahkerja; 
                $totalhitunggajidummy+=$oprek[$oblok]['hitunggajidummy'];                    
//echo "error: blok|".$oblok." patokanupahkerja|".$patokanupahkerja."\n";            
            }

//            if($kamusbjr[$patokanblok]<$bjrpalingkecildipremi)$bjrnya=$bjrpalingkecildipremi; else
//                if($kamusbjr[$patokanblok]>$bjrpalingbesardipremi)$bjrnya=$bjrpalingbesardipremi; else
            // ga dipake lagi feb 10, 2017 tatang
                    $bjrnya=$kamusbjr[$patokanblok];

//            $totalpremi=($totalhitunghasilkerja-$patokannorma)*$kamuspremilebihbasis[substr($patokanblok,0,6)][$bjrnya];
//            $totalpremibasis=floor($totalhitunghasilkerja/$patokannorma)*$kamuspremibasis[substr($patokanblok,0,6)][$bjrnya];
//            
                    // ganti di bawah ini, feb 10, 2017 tatang
//            $totalpremi=($totalhitunghasilkerja-$patokannormaawal)*$kamuspremilebihbasis[$patokanblok];
                    // ganti lagi oct 1, 2020, tatang as request from soge
            $totalpremi=($totalhitunghasilkerja2-$ratabasis)*$ratapremilebihbasis;
//            echo "error : totalpremi = (".$totalhitunghasilkerja." - ".$ratabasis.") x ".$ratapremilebihbasis; exit;
//            $totalpremibasis=floor($totalhitunghasilkerja/$patokannormaawal)*$kamuspremibasis[$patokanblok];
                    // ganti lagi oct 1, 2020, tatang as request from soge
            $totalpremibasis=floor($totalhitunghasilkerja2/$ratabasis)*$ratapremibasis;
            
//            echo "error".$patokanblok.":(".$totalhitunghasilkerja."/".$patokannormaawal.")*".$kamuspremibasis[$patokanblok]; exit;
            
//            echo "error".$patokanblok.":(".$totalhitunghasilkerja."/".$patokannormaawal.")*".$kamuspremibasis[$patokanblok]; exit;

            $selisihgaji=$patokanupahkerja-$totalhitunggajidummy;

            // error:115 160. 115 harusnya 191
//            echo "error:".$patokanupahkerja." ".$totalhitunggajidummy; exit;

            foreach($oprekblok as $oblok){
                if($totalhitunghasilkerja2<$ratabasis){ // total lebih kecil dari output minimal
    //                @$oprek[$oblok]['hitungupahpenalty']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$oprek[$oblok]['upahpenalty'];
                    @$oprek[$oblok]['hitungupahpenalty']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$selisihgaji;
                    if($kasuskhusus==true)$oprek[$oblok]['hitungupahpenalty']=0;
                }else{ // total dapat output minimal
                    $oprek[$oblok]['hitungupahpenalty']=0;
                }
                if($totalhitunghasilkerja2>=$ratabasis){
                    @$oprek[$oblok]['hitungpremi']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$totalpremi;
//                    echo "error: hitungpremi = ".$oprek[$oblok]['hasilkerja']." / ".$totalhasilkerja." x ".$totalpremi; exit;
    //                @$oprek[$oblok]['hitungupahpenalty']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$oprek[$oblok]['upahpenalty'];
                    @$oprek[$oblok]['hitungpremibasis']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$totalpremibasis;
                }else{
                    $oprek[$oblok]['hitungpremi']=0;
                    $oprek[$oblok]['hitungpremibasis']=0;
                }          
            }    
}            

//error:UPDATE `owldb`.`kebun_prestasi` SET `upahkerja` = '38203',
//                    `upahpenalty` = '0', `upahpremi` = '-2778',
//                    `premibasis` = '0'
//                    WHERE `notransaksi` = '20181116/SOGE/PNN/002' and `kodeorg` ='SOGE01E018' and `kodekegiatan` ='0' and `nik` = '0000001066'
        
            foreach($oprekblok as $oblok){
                $query="UPDATE `".$dbname."`.`kebun_prestasi` SET `upahkerja` = '".round($oprek[$oblok]['hitungupahkerja'])."',
                    `upahpenalty` = '".round($oprek[$oblok]['hitungupahpenalty'])."', `upahpremi` = '".round($oprek[$oblok]['hitungpremi'])."',
                    `premibasis` = '".round($oprek[$oblok]['hitungpremibasis'])."'
                    WHERE `notransaksi` = '".$oprek[$oblok]['notransaksi']."' and `kodeorg` ='".$oblok."' and `kodekegiatan` ='0' and `nik` = '".$param['nik']."'";
//                echo "error:".$query; exit;
                if(!mysql_query($query)) {
                    echo "DB Error : ".mysql_error();
                    exit;
                }            
            }

            $data['upahkerja']=round($oprek[$param['kodeorg']]['hitungupahkerja']);        
            $data['upahpenalty']=round($oprek[$param['kodeorg']]['hitungupahpenalty']);        
            $data['upahpremi']=round($oprek[$param['kodeorg']]['hitungpremi']);        
            $data['premibasis']=round($oprek[$param['kodeorg']]['hitungpremibasis']);                   
        } 

        // end of CADSHBDDB
        }else{ // kalo libur, proporsi gaji n dendanya saja 2018-11-26
            $firstKary = $param['nik'];
            $qUMR = selectQuery($dbname,'sdm_5gajipokok','sum(jumlah) as nilai',
                "karyawanid='".$firstKary."' and tahun='".substr($param['notransaksi'],0,4)."' and idkomponen in (1,31)");
            $Umr = fetchData($qUMR);
            $upahharian=round($Umr[0]['nilai']/25);
                        
            $query = "SELECT a.notransaksi, a.nik, a.kodeorg, a.luaspanen,
                a.hasilkerja, a.norma, a.outputminimal, a.upahkerja, a.upahpenalty, a.upahpremi, a.premibasis FROM ".$dbname.".`kebun_prestasi` a
                LEFT JOIN ".$dbname.".`kebun_aktifitas` b on a.notransaksi = b.notransaksi
                WHERE a.`kodekegiatan` = 0 and b.`tanggal` = '".$tanggalnya."' and a.`nik` = '".$param['nik']."'
                ";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail)){
                $oprekblok[$rDetail['kodeorg']]=$rDetail['kodeorg'];
                $oprek[$rDetail['kodeorg']]['hasilkerja']=$rDetail['hasilkerja'];
                $totalhasilkerja+=$rDetail['hasilkerja'];
            }
            if(count($oprekblok)>1){
                foreach($oprekblok as $oblok){
                    @$oprek[$oblok]['hitungupahkerja']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$upahharian;
//                    echo "error: ".$oprek[$oblok]['hitungupahkerja']."=".$oprek[$oblok]['hasilkerja']."/".$totalhasilkerja."*".$upahharian."\n";
                }
                foreach($oprekblok as $oblok){
                    $query="UPDATE `".$dbname."`.`kebun_prestasi` SET `upahkerja` = '".round($oprek[$oblok]['hitungupahkerja'])."',
                        `upahpenalty` = '".round($oprek[$oblok]['hitungupahkerja'])."'
                        WHERE `notransaksi` = '".$param['notransaksi']."' and `kodeorg` ='".$oblok."' and `kodekegiatan` ='0' and `nik` = '".$param['nik']."'";
    //                echo "error:".$query; exit;
                    if(!mysql_query($query)) {
                        echo "DB Error : ".mysql_error();
                        exit;
                    }            
                }                                    
            }
        }
	echo json_encode($param);                
        
	break;
    case 'delete':
	$where = "notransaksi='".$param['notransaksi']."' and nik='".$param['nik'].
	    "' and kodeorg='".$param['kodeorg']."'";
	$query = "delete from `".$dbname."`.`kebun_prestasi` where ".$where;
	if(!mysql_query($query)) {
	    echo "DB Error : ".mysql_error();
	    exit;
	}
        
        // cari tanggal
        $query = "SELECT distinct tanggal
            FROM ".$dbname.".`kebun_prestasi_vw`
            WHERE `notransaksi` = '".$param['notransaksi']."'
            ";
//        echo "error:".$query; exit;
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $tanggalnya=$rDetail['tanggal'];
        }        
        
        // cari luas panen orang
        $query = "SELECT sum(luaspanen) as luaspanen
            FROM ".$dbname.".`kebun_prestasi_vw`
            WHERE `tanggal` = '".$tanggalnya."' and `karyawanid` ='".$param['nik']."' and kodeorg != '".$param['kodeorg']."'
            ";
//        echo "error:".$query; exit;
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $luaspanenorang=$rDetail['luaspanen'];
        }
//        $luaspanenorang+=$data['luaspanen'];        
        
        if(($regional!='KALTIM')and($libur==false)){ // kalo libur ga pake proporsi dz: 20170223
        // cek apakah dalam satu hari bekerja di dua blok (CADSHBDDB)
        // dz: 20150226
            
        $query = "SELECT a.notransaksi, a.nik, a.kodeorg, a.luaspanen,
            a.hasilkerja, a.norma, a.outputminimal, a.upahkerja, a.upahpenalty, a.upahpremi, a.premibasis FROM ".$dbname.".`kebun_prestasi` a
            LEFT JOIN ".$dbname.".`kebun_aktifitas` b on a.notransaksi = b.notransaksi
            WHERE a.`kodekegiatan` = 0 and b.`tanggal` = '".$tanggalnya."' and a.`nik` = '".$param['nik']."'
            ";
//        echo "</br>error:".$query; exit;
        $patokanhasilkerja=0;
        $patokannorma=0;
        $patokanupahkerja=$param['upahkerja'];
        $totalhasilkerja=0;
        $patokanblok='';
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        $kasuskhusus=false;
        $jumlahluaspanendalamsehari=0;
        $jumlahluaskalioutputminimaldalamsehari=0;
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
         //   $oprekblok[$rDetail['kodeorg']]=$rDetail['kodeorg'];
            // if($proporsi6ha==true){ // proporsi6ha = 1
                
            // }else{ // proporsi6ha = 0
            //     if($kamusluas[$rDetail['kodeorg']]<6)$kasuskhusus=true;
            // }            
//            if($rDetail['kodeorg']=='MRKE01A02A')$kasuskhusus=true;
            $oprek[$rDetail['kodeorg']]['kodeorg']=$rDetail['kodeorg'];
            $oprek[$rDetail['kodeorg']]['hasilkerja']=$rDetail['hasilkerja'];
            $oprek[$rDetail['kodeorg']]['norma']=$rDetail['norma'];
            $oprek[$rDetail['kodeorg']]['outputminimal']=$rDetail['outputminimal'];
            $oprek[$rDetail['kodeorg']]['upahkerja']=$rDetail['upahkerja'];
//            $oprek[$rDetail['kodeorg']]['upahpenalty']=$rDetail['upahpenalty'];
            $oprek[$rDetail['kodeorg']]['upahpremi']=$rDetail['upahpremi'];
            $oprek[$rDetail['kodeorg']]['hasilkerja']=$rDetail['hasilkerja'];
            $oprek[$rDetail['kodeorg']]['premibasis']=$rDetail['premibasis'];
            
            $oprek[$rDetail['kodeorg']]['notransaksi']=$rDetail['notransaksi'];
            
            if($rDetail['hasilkerja']>$patokanhasilkerja){
                $patokanhasilkerja=$rDetail['hasilkerja'];
                $patokannorma=$rDetail['norma'];   
//                $patokanoutputminimal=$rDetail['outputminimal'];
                $patokanblok=$rDetail['kodeorg'];
            }            
            $totalhasilkerja+=$rDetail['hasilkerja'];
            
            // patokan output minimal hitung berdasarkan luas panen (tatang 2016-09-20)
            $jumlahluaspanendalamsehari+=$rDetail['luaspanen'];
            $jumlahluaskalioutputminimaldalamsehari+=($rDetail['outputminimal']*$rDetail['luaspanen']);
            @$patokanoutputminimal=$jumlahluaskalioutputminimaldalamsehari/$jumlahluaspanendalamsehari;
//            echo "error:patoutmin(".$patokanoutputminimal.")=jumluaxoutmindalseh(".$jumlahluaskalioutputminimaldalamsehari.")/jumluapandalseh(".$jumlahluaspanendalamsehari.")\n";
            // patokan output minimal (end)            
        }    
        
            // cek yang bisa panen berdasarkan taksasi
            $luastaksasi=0;
            $hktaksasi=0;
            $query = "SELECT *
                FROM ".$dbname.".`kebun_taksasi` a
                WHERE a.`tanggal` = '".$tanggalnya."' and a.`blok` = '".$patokanblok."' and `posting` = 1
                ";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $luastaksasi=($rDetail['hasisa']+$rDetail['haesok']);
//                $hktaksasi=$rDetail['hkdigunakan'];
                $jjgmasak=$rDetail['jjgmasak'];
                $jjgoutput=$rDetail['jjgoutput'];
                $akp=$rDetail['persenbuahmatang'];
            }

            @$hktaksasi=ceil($jjgmasak/$jjgoutput);


                // tambahan tatang
//                $luasoutput=$luastaksasi/$hktaksasi;             
//                
//                echo "error: ".$luastaksasi."/".$hktaksasi; exit;
//                
//    $sorg="select kodeorg, jumlahpokok as pokokthnini, luasareaproduktif as hathnini from ".$dbname.".setup_blok where kodeorg ='".$patokanblok."'";
//    $qorg=mysql_query($sorg) or die(mysql_error($conn));
//    while($rorg=mysql_fetch_assoc($qorg)){
//        $pokok=$rorg['pokokthnini'];
//        $luas=$rorg['hathnini'];
//    }
//    @$sph=round($pokok/$luas);  
//    @$batasakp=($patokannorma*2)/($sph*4)*100;                                
//                
                    $patokannormaawal=$patokannorma;
////                    echo "error: patokannorma:".$patokannorma; exit;
//            // =================================================================    
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){
////                if($luasoutput<4){ // akp tinggi
//                if($akp>$batasakp){ // akp tinggi
//                    $patokanoutputminimal=round(2*$patokannorma);                    
//                    $patokannorma=round(2*$patokannorma);           
//                    $akape="AKP tinggi";
//                }else{ // akp rendah
//                    $akape="AKP rendah";                    
//                }                
//            }                
//            // =================================================================    
        
        if(count($oprekblok)>1){
//            echo "error: blok utama: ".$patokanblok." basis: ".$patokannorma." hasil: ".$totalhasilkerja." akp: ".$akp." batasakp: ".$batasakp." ".$akape."\n";
//            echo "error: sph: ".$sph." pokok: ".$pokok." luas: ".$luas." luaspanen: ".$luaspanenorang."\n";
            // kamus premi basis
//            $query = "SELECT afdeling, bjr, basis, premibasis, premilebihbasis
//                FROM ".$dbname.".`kebun_5basispanen2`
//                WHERE afdeling LIKE '".substr($param['kodeorg'],0,6)."%' 
//                ";
//            $qDetail=mysql_query($query) or die(mysql_error($conn));
//            while($rDetail=mysql_fetch_assoc($qDetail))
//            {
//                $kamuspremibasis[$rDetail['afdeling']][$rDetail['bjr']]=$rDetail['premibasis'];
//                $kamuspremilebihbasis[$rDetail['afdeling']][$rDetail['bjr']]=$rDetail['premilebihbasis'];
//                $bjrbjrbjr[$rDetail['bjr']]=$rDetail['bjr'];
//            }
//            $bjrpalingkecildipremi=min($bjrbjrbjr);
//            $bjrpalingbesardipremi=max($bjrbjrbjr);
            // pindah di bawah feb 10, 2017 tatang

            // kamus bjr
            $query = "SELECT kodeorg, bjr, basis, premibasis, premilebihbasis 
                FROM ".$dbname.".`kebun_5bjr`
                WHERE tahunproduksi = '".substr($tanggalnya,0,4)."' and kodeorg like '".substr($param['kodeorg'],0,4)."%'
                ORDER BY bjr";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $kamusbjr[$rDetail['kodeorg']]=$rDetail['bjr'];
                $bjrbjrbjr[$rDetail['bjr']]=$rDetail['bjr'];
                $kamusbasis[$rDetail['kodeorg']]=$rDetail['basis'];
                $kamuspremibasis[$rDetail['kodeorg']]=$rDetail['premibasis'];
                $kamuspremilebihbasis[$rDetail['kodeorg']]=$rDetail['premilebihbasis'];
            }
            
            // kamus akp
            $query = "SELECT blok, hasisa, haesok, jmlhpokok, persenbuahmatang
                FROM ".$dbname.".`kebun_taksasi`
                WHERE tanggal = '".$tanggalnya."' and blok like '".substr($param['kodeorg'],0,4)."%'";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail))
            {
                $kamusakp[$rDetail['blok']]=$rDetail['persenbuahmatang'];
                @$kamussph[$rDetail['blok']]=$rDetail['jmlhpokok']/($rDetail['hasisa']+$rDetail['haesok']);
            }            
            foreach($oprekblok as $oblok){
                $totalakp+=$kamusakp[$oblok];
                $totalsph+=$kamussph[$oblok];
                $totalbasis+=$kamusbasis[$oblok];
                $totalpremibasis+=$kamuspremibasis[$oblok];
                $totalpremilebihbasis+=$kamuspremilebihbasis[$oblok];
            }
            $rataakp=$totalakp/count($oprekblok);
            $ratasph=$totalsph/count($oprekblok);
            $ratabasis=$totalbasis/count($oprekblok);
            $ratapremibasis=$totalpremibasis/count($oprekblok);
            $ratapremilebihbasis=$totalpremilebihbasis/count($oprekblok);
            
            // janjangminimal
            $janjangminimal=$ratasph*4*$rataakp/100;
            if($day=='Fri'){
                @$ratabasis=5/7*$ratabasis;
                @$janjangminimal=5/7*$janjangminimal;
            }
            $ratabasis=round($ratabasis);               
            $janjangminimal=round($janjangminimal);               


        $basisluas=4;
        if($day=='Fri'){
            $basisluas=5/7*$basisluas;
        }
            // ini yang lama
//            if($janjangminimal>(2*$ratabasis)){
//                $akape="AKP tinggi";
//            }else{
//                $akape="AKP rendah";
//            }
            $ratabatasakp=($ratabasis*2)/($ratasph*$basisluas)*100;
            if($rataakp>$ratabatasakp){
                $akape="AKP tinggi";
            }else{
                $akape="AKP rendah";
            }

// remove temp 20200915            
//if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){
//    if($akape=="AKP tinggi"){
//        $basisnyajadi=$ratabasis*2;
//    }else{ // AKP rendah
//        $basisnyajadi=$ratabasis;
//    }
//    
//    // ini ga usah karena masing2 udah di5/7kan
////    if($day=='Fri'){
////        $basisnyajadi=5/7*$basisnyajadi;
////    }
////    $basisnyajadi=round($basisnyajadi);    
//    
//    @$patokanupahpenalty=$patokanupahkerja-(($totalhasilkerja/$basisnyajadi)*$patokanupahkerja);
//    if($patokanupahpenalty>0){
//        $patokanpremibasis=0;
//        $patokanpremilebihbasis=0;
//    }else{
//        @$patokanpremibasis=floor($totalhasilkerja/$ratabasis)*$ratapremibasis;
//        @$patokanpremilebihbasis=($totalhasilkerja-$ratabasis)*$ratapremilebihbasis;        
//    }
//    
//    if($totalhasilkerja>=$janjangminimal){ // kalo udah dapet 4 ha, ga kena proporsi...
//        $patokanupahpenalty=0;
//    }    
//    $basiswaktunya=4;
//    if($day=='Fri'){
//        $basiswaktunya=5/7*$basiswaktunya;
//    }
//    if($rataakp>$ratabatasakp){ // akp tinggi
//        
//    }else{ // akp rendah
//        // tambahan tatang 2018-11-31, kalo luas ga dapet, proporsi berdasarkan hektar
//        if(($jumlahluaspanendalamsehari<$basiswaktunya)and($patokanupahpenalty<=0)){
////            echo "error: masuk sini"; exit;
//            @$patokanupahpenalty=($basiswaktunya-$jumlahluaspanendalamsehari)/$basiswaktunya*$patokanupahkerja;
//        }
//    }
//    
//    
////            echo "error: janjangminimal:".$janjangminimal."\n";
////            echo "error: luaspanenorang:".$luaspanenorang."\n";
////            echo "error: rataakp:".$rataakp."\n";
////            echo "error: janjangminimal:".$janjangminimal."\n";
////            echo "error: patokanupahpenalty:".$patokanupahpenalty."\n";
////            echo "error: :".$patokanupahkerja." - ((".$totalhasilkerja."/".$basisnyajadi.") * ".$patokanupahkerja." \n";
////            exit;    
//        
//    foreach($oprekblok as $oblok){
//        if($patokanupahkerja>0)
//        @$oprek[$oblok]['hitungupahkerja']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$patokanupahkerja;
//        
////        if($totalhasilkerja<$basisnyajadi){ // ga dapet basis
//        if($patokanupahpenalty>0)
//            @$oprek[$oblok]['hitungupahpenalty']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$patokanupahpenalty;
////        }else{ // dapet basis
//        if($patokanpremibasis>0)
//            @$oprek[$oblok]['hitungpremibasis']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$patokanpremibasis;
//        if($patokanpremilebihbasis>0)
//            @$oprek[$oblok]['hitungpremi']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$patokanpremilebihbasis;
////        }
//    }
//    
//}else
    {
            // kalo hari jumat basisnya 5/7
            if($day=='Fri'){
                $patokannorma=5/7*$patokannorma;
            }
            $patokannorma=round($patokannorma);

            $totalpatokanhasilkerja=0;
            foreach($oprekblok as $oblok){
//                echo "error: ".$patokannorma."/".$oprek[$oblok]['norma']."*".$oprek[$oblok]['hasilkerja']."\n";
                @$oprek[$oblok]['hitunghasilkerja']=$patokannorma/$oprek[$oblok]['norma']*$oprek[$oblok]['hasilkerja'];
                @$oprek[$oblok]['hitungupahkerja']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$patokanupahkerja;
                $totalhitunghasilkerja+=$oprek[$oblok]['hitunghasilkerja'];
                $totalhitunghasilkerja2+=$oprek[$oblok]['hasilkerja'];
    //            @$oprek[$oblok]['upahpenalty']=(-1)*($oprek[$oblok]['hasilkerja']-$oprek[$oblok]['norma'])/$oprek[$oblok]['norma']*$patokanupahkerja;            
                @$oprek[$oblok]['hitunggajidummy']=$oprek[$oblok]['hasilkerja']/$oprek[$oblok]['norma']*$patokanupahkerja; 
                $totalhitunggajidummy+=$oprek[$oblok]['hitunggajidummy'];                    
//echo "error: blok|".$oblok." patokanupahkerja|".$patokanupahkerja."\n";            
            }

//            if($kamusbjr[$patokanblok]<$bjrpalingkecildipremi)$bjrnya=$bjrpalingkecildipremi; else
//                if($kamusbjr[$patokanblok]>$bjrpalingbesardipremi)$bjrnya=$bjrpalingbesardipremi; else
            // ga dipake lagi feb 10, 2017 tatang
                    $bjrnya=$kamusbjr[$patokanblok];

//            $totalpremi=($totalhitunghasilkerja-$patokannorma)*$kamuspremilebihbasis[substr($patokanblok,0,6)][$bjrnya];
//            $totalpremibasis=floor($totalhitunghasilkerja/$patokannorma)*$kamuspremibasis[substr($patokanblok,0,6)][$bjrnya];
                    // ganti di bawah ini, feb 10, 2017 tatang
//            $totalpremi=($totalhitunghasilkerja-$patokannormaawal)*$kamuspremilebihbasis[$patokanblok];
                    // ganti lagi oct 1, 2020, tatang as request from soge
            $totalpremi=($totalhitunghasilkerja2-$ratabasis)*$ratapremilebihbasis;
//            echo "error : totalpremi = (".$totalhitunghasilkerja." - ".$ratabasis.") x ".$ratapremilebihbasis; exit;
//            $totalpremibasis=floor($totalhitunghasilkerja/$patokannormaawal)*$kamuspremibasis[$patokanblok];
                    // ganti lagi oct 1, 2020, tatang as request from soge
            $totalpremibasis=floor($totalhitunghasilkerja2/$ratabasis)*$ratapremibasis;
//            echo "error".$patokanblok.":(".$totalhitunghasilkerja."/".$patokannormaawal.")*".$kamuspremibasis[$patokanblok]; exit;

            $selisihgaji=$patokanupahkerja-$totalhitunggajidummy;

            // error:115 160. 115 harusnya 191
//            echo "error:".$patokanupahkerja." ".$totalhitunggajidummy; exit;

            foreach($oprekblok as $oblok){
                if($totalhitunghasilkerja2<$ratabasis){ // total lebih kecil dari output minimal
    //                @$oprek[$oblok]['hitungupahpenalty']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$oprek[$oblok]['upahpenalty'];
                    @$oprek[$oblok]['hitungupahpenalty']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$selisihgaji;
                    if($kasuskhusus==true)$oprek[$oblok]['hitungupahpenalty']=0;
                }else{ // total dapat output minimal
                    $oprek[$oblok]['hitungupahpenalty']=0;
                }
                if($totalhitunghasilkerja2>=$ratabasis){
                    @$oprek[$oblok]['hitungpremi']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$totalpremi;
                    @$oprek[$oblok]['hitungpremibasis']=($oprek[$oblok]['hasilkerja']/$totalhasilkerja)*$totalpremibasis;
                }else{
                    $oprek[$oblok]['hitungpremi']=0;
                    $oprek[$oblok]['hitungpremibasis']=0;
                }          
            }    
}            

            foreach($oprekblok as $oblok){
                $query="UPDATE `".$dbname."`.`kebun_prestasi` SET `upahkerja` = '".round($oprek[$oblok]['hitungupahkerja'])."',
                    `upahpenalty` = '".round($oprek[$oblok]['hitungupahpenalty'])."', `upahpremi` = '".round($oprek[$oblok]['hitungpremi'])."',
                    `premibasis` = '".round($oprek[$oblok]['hitungpremibasis'])."'
                    WHERE `notransaksi` = '".$oprek[$oblok]['notransaksi']."' and `kodeorg` ='".$oblok."' and `kodekegiatan` ='0' and `nik` = '".$param['nik']."'";
//                echo "error:".$query; exit;
                if(!mysql_query($query)) {
                    echo "DB Error : ".mysql_error();
                    exit;
                }            
            }

            $data['upahkerja']=round($oprek[$param['kodeorg']]['hitungupahkerja']);        
            $data['upahpenalty']=round($oprek[$param['kodeorg']]['hitungupahpenalty']);        
            $data['upahpremi']=round($oprek[$param['kodeorg']]['hitungpremi']);        
            $data['premibasis']=round($oprek[$param['kodeorg']]['hitungpremibasis']);                   
        } 

        // end of CADSHBDDB
        }else{ // kalo libur, proporsi gaji n dendanya saja 2018-11-26
            $firstKary = $param['nik'];
            $qUMR = selectQuery($dbname,'sdm_5gajipokok','sum(jumlah) as nilai',
                "karyawanid='".$firstKary."' and tahun='".substr($param['notransaksi'],0,4)."' and idkomponen in (1,31)");
            $Umr = fetchData($qUMR);
            $upahharian=round($Umr[0]['nilai']/25);
                        
            $query = "SELECT a.notransaksi, a.nik, a.kodeorg, a.luaspanen,
                a.hasilkerja, a.norma, a.outputminimal, a.upahkerja, a.upahpenalty, a.upahpremi, a.premibasis FROM ".$dbname.".`kebun_prestasi` a
                LEFT JOIN ".$dbname.".`kebun_aktifitas` b on a.notransaksi = b.notransaksi
                WHERE a.`kodekegiatan` = 0 and b.`tanggal` = '".$tanggalnya."' and a.`nik` = '".$param['nik']."'
                ";
            $qDetail=mysql_query($query) or die(mysql_error($conn));
            while($rDetail=mysql_fetch_assoc($qDetail)){
                $oprekblok[$rDetail['kodeorg']]=$rDetail['kodeorg'];
                $oprek[$rDetail['kodeorg']]['hasilkerja']=$rDetail['hasilkerja'];
                $totalhasilkerja+=$rDetail['hasilkerja'];
            }
            if(count($oprekblok)>1){
                foreach($oprekblok as $oblok){
                    @$oprek[$oblok]['hitungupahkerja']=$oprek[$oblok]['hasilkerja']/$totalhasilkerja*$upahharian;
                }
                foreach($oprekblok as $oblok){
                    $query="UPDATE `".$dbname."`.`kebun_prestasi` SET `upahkerja` = '".round($oprek[$oblok]['hitungupahkerja'])."',
                        `upahpenalty` = '".round($oprek[$oblok]['hitungupahkerja'])."'
                        WHERE `notransaksi` = '".$oprek[$oblok]['notransaksi']."' and `kodeorg` ='".$oblok."' and `kodekegiatan` ='0' and `nik` = '".$param['nik']."'";
    //                echo "error:".$query; exit;
                    if(!mysql_query($query)) {
                        echo "DB Error : ".mysql_error();
                        exit;
                    }            
                }                                
            }            
        }


        
	break;
    case 'updTahunTanam':
	$query = selectQuery($dbname,'setup_blok','kodeorg,tahuntanam',
	    "kodeorg='".$param['kodeorg']."'");
	$res = fetchData($query);
	if(!empty($res)) {
	    echo $res[0]['tahuntanam'];
	} else {
	    echo '0';
	}
	break;
    case 'updBjr':
        
        // KALO ADA UPDATE DI SINI, UPDATE JUGA YANG ADA DI KEBUN_SLAVE_TAKSASI: getSPH
        
        $tahuntahuntahun=substr($param['notransaksi'],0,4);
        $bulanbulanbulan=substr($param['notransaksi'],4,2); 
	$firstKary = $param['nik'];
        $tanggal=$param['tanggal'];
        $tanggal=tanggalsystem($tanggal);
        
        $hari=date('l', strtotime($tanggal)); 
        if($tanggal=='20190601')$hari='Friday'; // ganti hari 2019 2019-06-07

    // tambahan basis jumat karena hujan, tatang 2020-01-10 by Manusia Planet
    $tanggalbj=date('Y-m-d', strtotime($tanggal));
    $adabj=false;
    $str="select kode, kodeorg, nama, parameter, lastuser, lastupdate from ".$dbname.".kebun_5kontrol
        where kode = 'basisjumat' and kodeorg = '".$_SESSION['empl']['lokasitugas']."' and parameter = '".$tanggalbj."'
        ";
//    echo "error: ".$str;
    $res=mysql_query($str);
    while($bar=mysql_fetch_object($res)){
        if($bar->parameter==$tanggalbj)$adabj=true;
    }
    if($adabj)$hari='Friday';
//    echo "error: ".$hari." ".$str;
    // end of tambahan basis jumat             
        
//        echo "error: ".$param['tanggal'];
        
        if($bulanbulanbulan=='01'){
            $bulanbulanbulan='12';
            $tahuntahuntahun-=1;
        }else{
            $bulanbulanbulan-=1;
            if(strlen($bulanbulanbulan)==1)$bulanbulanbulan='0'.$bulanbulanbulan;
        }
        
        $janjangjanjangjanjang=$param['hasilkerja'];
        $luaspanen=$param['luaspanen'];
        $afdelingafdelingafdeling=substr($param['kodeorg'],0,6);  
        
//        // cek spb vs tiket
//        $spbbelumdiinput='';
//        $query = "SELECT a.nospb, b.tanggal
//            FROM ".$dbname.".`pabrik_timbangan` a
//            LEFT JOIN ".$dbname.".kebun_spbht b ON a.nospb = b.nospb
//            WHERE a.`tanggal` LIKE '".$tahuntahuntahun."-".$bulanbulanbulan."%' and a.`kodeorg` = '".substr($param['kodeorg'],0,4)."'
//                AND b.`tanggal` is NULL";
//        $qDetail=mysql_query($query) or die(mysql_error($conn));
//        while($rDetail=mysql_fetch_assoc($qDetail))
//        {
//            $spbbelumdiinput.=$rDetail['nospb'].', ';
//        }        
//        if($spbbelumdiinput!=''){
//            $spbbelumdiinput=substr($spbbelumdiinput,0,-2);
//            echo "WARNING: Ada SPB bulan lalu yang belum diinput: ".$spbbelumdiinput;
//            exit;
//        }
//
//        $spbbelumdiposting='';
//        $query = "SELECT nospb, tanggal
//            FROM ".$dbname.".`kebun_spb_vw`
//            WHERE `tanggal` LIKE '".$tahuntahuntahun."-".$bulanbulanbulan."%' and `blok` like '".substr($param['kodeorg'],0,4)."%'
//                and posting = 0
//                ";
//        $qDetail=mysql_query($query) or die(mysql_error($conn));
//        while($rDetail=mysql_fetch_assoc($qDetail))
//        {
//            $spbbelumdiposting.=$rDetail['nospb'].', ';
//        }        
//        if($spbbelumdiposting!=''){
//            $spbbelumdiposting=substr($spbbelumdiposting,0,-2);
//            echo "WARNING: Ada SPB bulan lalu yang belum diposting: ".$spbbelumdiposting;
//            exit;
//        }        
        
        // ambil bjr budget
        $query = "SELECT a.kodeblok, a.thntnm, b.bjr
            FROM ".$dbname.".`bgt_blok` a
            LEFT JOIN ".$dbname.".bgt_bjr b ON a.tahunbudget = b.tahunbudget
                AND substr( a.kodeblok, 1, 4 ) = b.kodeorg
                AND a.thntnm = b.thntanam
            WHERE a.`tahunbudget` =".$tahuntahuntahun."
                AND a.`kodeblok` LIKE '".$param['kodeorg']."'";
	$res = fetchData($query);
	if(!empty($res)) {
            $bjr=$res[0]['bjr'];
	}
                
// ambil bjr sesuaikan dengan algoritma LBM (lbm_slave_produksi_perblok.php)        
//$sProd="select distinct * from ".$dbname.".kebun_spb_bulanan_vw 
//        where blok like '".$param['kodeorg']."' and periode = '".$tahuntahuntahun."-".$bulanbulanbulan."'
//        ";
//$qProd=mysql_query($sProd) or die(mysql_error($conn));
//while($rProd=  mysql_fetch_assoc($qProd))
//{
//    $dtKgBi=$rProd['nettotimbangan'];
//}        
//$sJjg="select distinct sum(hasilkerja) as jjg,left(tanggal,7) as periode,kodeorg from ".$dbname.".kebun_prestasi_vw 
//       where kodeorg like '".$param['kodeorg']."' and left(tanggal,7) = '".$tahuntahuntahun."-".$bulanbulanbulan."'
//       ";
//$qJjg=mysql_query($sJjg) or die(mysql_error($conn));
//while($rJjg=  mysql_fetch_assoc($qJjg))
//{
//    $jjgpanen=$rJjg['jjg'];
//}
//@$bjr=$dtKgBi/$jjgpanen;        

        $basis=0;
        // cek bjr via SETUP
        $query = "SELECT bjr, basis, premibasis, premilebihbasis
            FROM ".$dbname.".`kebun_5bjr` a
            WHERE a.`tahunproduksi` = '".substr($param['notransaksi'],0,4)."' and a.`kodeorg` = '".$param['kodeorg']."'
            ";
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $bjr=$rDetail['bjr'];
            $basis=$rDetail['basis'];
            $premibasis=$rDetail['premibasis'];            
            $premilebihbasis=$rDetail['premilebihbasis'];            
        }            
        
        // dah ga dipake, pindahin basis2an ke atas, feb 10, 2017 tatang
//        // ambil basis yang paling kecil
//        $query = "SELECT bjr, afdeling, basis, premibasis, premilebihbasis
//            FROM ".$dbname.".`kebun_5basispanen2`
//            WHERE afdeling LIKE '".$afdelingafdelingafdeling."' order by bjr asc limit 1
//            ";
//	$res = fetchData($query);
//	if(!empty($res)) {
//            $bjrpalingkecil=$res[0]['bjr'];
//	}
//        // ambil basis yang paling besar
//        $query = "SELECT bjr, afdeling, basis, premibasis, premilebihbasis
//            FROM ".$dbname.".`kebun_5basispanen2`
//            WHERE afdeling LIKE '".$afdelingafdelingafdeling."' order by bjr desc limit 1
//            ";
//	$res = fetchData($query);
//	if(!empty($res)) {
//            $bjrpalingbesar=$res[0]['bjr'];          
//	}
//        
//        $bjr2=$bjr;
//        if($bjr<$bjrpalingkecil)$bjr2=$bjrpalingkecil;
//        if($bjr>$bjrpalingbesar)$bjr2=$bjrpalingbesar;
//        
//        // ambil basis berdasarkan bjr + afdeling
//        $query = "SELECT afdeling, basis, premibasis, premilebihbasis
//            FROM ".$dbname.".`kebun_5basispanen2`
//            WHERE afdeling LIKE '".$afdelingafdelingafdeling."' and bjr = ".round($bjr2,2)."
//            ";
//	$res = fetchData($query);
//	if(!empty($res)) {
//            $basis=$res[0]['basis'];
//            $premibasis=$res[0]['premibasis'];            
//            $premilebihbasis=$res[0]['premilebihbasis'];            
//	}
        
        // kalo hari jumat basisnya 5/7
        if($hari=='Friday'){
            @$basis=5/7*$basis;
        }
        $basis=round($basis);
//        echo "error: ".$hari." ".$basis;
        // itung premi lebih basis
        $lebihbasis=$janjangjanjangjanjang-$basis;
        if($lebihbasis>0){
            $premilebihbasis=$lebihbasis*$premilebihbasis;            
        }else{
            $premilebihbasis=0;
        }
        
        //update upah penalty
	$qUMR = selectQuery($dbname,'sdm_5gajipokok','sum(jumlah) as nilai',
	    "karyawanid=".$firstKary." and tahun=".substr($param['notransaksi'],0,4)." and idkomponen in (1,31)");
	$Umr = fetchData($qUMR);        
        $hasilkerja=$param['hasilkerja'];
        
        // cek yang bisa panen berdasarkan taksasi
        $query = "SELECT *
            FROM ".$dbname.".`kebun_taksasi` a
            WHERE a.`tanggal` = '".substr($param['notransaksi'],0,8)."' and a.`blok` = '".$param['kodeorg']."' and `posting` = 1
            ";
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        $luastaksasi=0;
        $hktaksasi=0;
        $jjgmasak=0;
        $akp=0;
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $luastaksasi=($rDetail['hasisa']+$rDetail['haesok']);
//            $hktaksasi=$rDetail['hkdigunakan'];
            $jjgmasak=$rDetail['jjgmasak'];
            $jjgoutput=$rDetail['jjgoutput'];
            
            $akp=$rDetail['persenbuahmatang'];
        }
        if($akp==0){
//          echo "error: AKP ".$param['kodeorg']." ".$akp;
//            exit;
        }
            @$hktaksasi=ceil($jjgmasak/$jjgoutput);

                // tambahan tatang, ganti jadi batasakp 2017-08-21
//                $luasoutput=$luastaksasi/$hktaksasi;        echo "error:(luout)".$luasoutput.'=(lutak)'.$luastaksasi."/(hktak)".$hktaksasi."(".$jjgmasak."/".$jjgoutput.")"; exit;
        
    $sorg="select kodeorg, jumlahpokok as pokokthnini, luasareaproduktif as hathnini from ".$dbname.".setup_blok where kodeorg ='".$param['kodeorg']."'";
    $qorg=mysql_query($sorg) or die(mysql_error($conn));
    while($rorg=mysql_fetch_assoc($qorg)){
        $pokok=$rorg['pokokthnini'];
        $luas=$rorg['hathnini'];
    }
    @$sph=round($pokok/$luas);        
    
//            @$hktaksasi=ceil($jjgmasak/$jjgoutput);
    
    $basisluas=4;
    if($hari=='Friday'){
        $basisluas=$basisluas*5/7;
    }
    
    @$batasakp=($basis*2)/($sph*$basisluas)*100;
    
//    echo "error: batasakp: ".$batasakp.", akp: ".$akp;
//    exit;
        
    $yangbisapanen=0;
        @$luasperhk=ceil($luastaksasi/$hktaksasi);
        if($luasperhk<=6){
            $yangbisapanen=$hktaksasi;            
        }else{
            $yangbisapanen=$luasperhk;
        }       
//        echo "error: yang bisa panen ".$hktaksasi;
//        exit();
        
        $upahharian=round($Umr[0]['nilai']/25);
        
            @$capaibasis=$hasilkerja/$basis;        
//            echo "error:".$tanggal; exit;
            
        // cari total luas panen karyawan ybs hari itu
        $query = "SELECT nik, luaspanen
            FROM ".$dbname.".`kebun_prestasi`
            WHERE `notransaksi` like '".substr($param['notransaksi'],0,8)."%' and `kodeorg` != '".$param['kodeorg']."' and `nik` = '".$param['nik']."'
            ";
//        echo "error: ".$query; exit;
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $totalluaspanen+=$rDetail['luaspanen'];
        }
        $totalluaspanen+=$param['luaspanen'];
            
        // itung premi basis (kalo 2x basis, dapet 2x... dst)
        @$kalibasis=floor($janjangjanjangjanjang/$basis);        
        $premibasis=$premibasis*$kalibasis;                    
        
        if($tanggal<'20140201'){ // sebelum tanggal 1 FEB 2014
//            @$batasproporsi=round(0.8*$basis);
//            if(($capaibasis>=(0.8))or($luaspanen>=6)){ // luas lebih 6 ha lon dibuang
//                $upahpenalty=0;
//            }else{
//                @$upahpenalty=round($Umr[0]['nilai']/25*($capaibasis));
//                $upahpenalty=$upahharian-$upahpenalty;
//            }            
        }else{ //setelah tanggal 1 FEB 2014 // batas diganti dari 6 jadi 4 by tatang 20181115
        // remove temp 20200915
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){ // hanya soge sene batasproporsinya ga pake 0.8 by tatang 20181129
//                @$batasproporsi=round($sph*$basisluas*$akp/100);
//                if($hasilkerja>=($batasproporsi)){ // luas lebih 6 ha dibuang
////                    echo "error: masuk sini."; exit;
//                    $upahpenalty=0;
//                }else{
//                    @$upahpenalty=round($Umr[0]['nilai']/25*($capaibasis));
////        echo "error: uh:".$upahharian." up".$upahpenalty." hk".$hasilkerja." bp".$batasproporsi." b".$basis; exit;
//                    $upahpenalty=$upahharian-$upahpenalty;
//                }                
//            }else
                {
            if($luasperhk <= $basisluas){
//                echo "error: masuk sini"; exit;
    //            if(($capaibasis>=(0.8))or($luaspanen>=6)){ // luas lebih 6 ha lon dibuang
                @$batasproporsi=round(0.8*$basis);
                if($capaibasis>=(0.8)){ // luas lebih 6 ha lon dibuang
//                    echo "error: masuk sini."; exit;
                    $upahpenalty=0;
                }else{
                    @$upahpenalty=round($Umr[0]['nilai']/25*($capaibasis));
                    $upahpenalty=$upahharian-$upahpenalty;
                }
            }else{
                @$batasproporsi=round($sph*$basisluas*$akp/100);
                if($hasilkerja>=($batasproporsi)){ // luas lebih 6 ha dibuang
//                    echo "error: masuk sini."; exit;
                    $upahpenalty=0;
                }else{
                    @$upahpenalty=round($Umr[0]['nilai']/25*($capaibasis));
//        echo "error: uh:".$upahharian." up".$upahpenalty." hk".$hasilkerja." bp".$batasproporsi." b".$basis; exit;
                    $upahpenalty=$upahharian-$upahpenalty;
                }
            }                 
            }
            
            
            // remove temp 20200915
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){
////                if($luasoutput<4){ // akp tinggi
//                if($akp>$batasakp){ // akp tinggi
//                    $batasproporsi=round(2*$basis);
//                    if($hasilkerja<$batasproporsi){
//                        @$capaibasis=$hasilkerja/$batasproporsi;                    
//                        @$upahpenalty=round($Umr[0]['nilai']/25*($capaibasis));
//                        $upahpenalty=$upahharian-$upahpenalty;
//                        $premibasis=0;
//                        $premilebihbasis=0;
//                    }                    
//                    
//                }else{ // akp rendah
////                    if($luaspanen>4){
//////                        $upahpenalty=0;
////                    }
//                    // tambahan tatang 2018-11-13
//                    
//                    if(($totalluaspanen<$basisluas)and($upahpenalty<=0)){ // jika janjang minimal tercapai, tapi target 4Ha tidak tercapai, proporsi berdasarkan luas
//                        @$upahpenalty=round((($basisluas-$luaspanen)/$basisluas)*($Umr[0]['nilai']/25));
////                        echo "error: ".$totalluaspanen."<".$basisluas." (".$basisluas."-".$luaspanen.")/".$basisluas.")*(".$Umr[0]['nilai']."/25)"; exit;
////                        error: 0<4 (4-4)/4)*(2626000/25)
////                    echo "error: masuk sini: ".$akp." ".$batasakp; exit;
//                    }
//                }                
//            }
            
        }
        
        if($upahpenalty<0)$upahpenalty=0;
//        error: 2626000 / 25 * 0.67619047619048 = 0
//        echo "error: ".$Umr[0]['nilai']." / 25 * ".$capaibasis." = ".$upahpenalty; exit;
        
        $hasilkerjakg=round($bjr*$janjangjanjangjanjang,2);
        $hasilhasilhasil=$hasilkerjakg.'##'.$basis.'##'.$premibasis.'##'.$premilebihbasis.'##'.$upahpenalty.'##'.$upahharian.'##'.$batasproporsi;
        echo $hasilhasilhasil;
	break;
        
    case 'updBjr2': // if($regional=='KALTIM')
        $tahuntahuntahun=substr($param['notransaksi'],0,4);
        $hasilhasilhasil=$param['hasilkerja'];
	$query = selectQuery($dbname,'kebun_5bjr','kodeorg,bjr',
	    "kodeorg='".$param['kodeorg']."' and tahunproduksi = '".$tahuntahuntahun."'");
	$res = fetchData($query);
	if(!empty($res)) {
            $hasilhasil=$hasilhasilhasil*$res[0]['bjr'];
	    echo $hasilhasil;
	} else {
	    echo '0';
	}

	break;        
    case 'updBjr3': // khusus hari libur
	$firstKary = $param['nik'];
	$qUMR = selectQuery($dbname,'sdm_5gajipokok','sum(jumlah) as nilai',
	    "karyawanid='".$firstKary."' and tahun='".substr($param['notransaksi'],0,4)."' and idkomponen in (1,31)");
	$Umr = fetchData($qUMR);
        $upahharian=round($Umr[0]['nilai']/25);        
        
        $tahuntahuntahun=substr($param['notransaksi'],0,4);
        $hasilhasilhasil=$param['hasilkerja'];
        $afdelingafdelingafdeling=substr($param['kodeorg'],0,6);  
        
	$query = selectQuery($dbname,'kebun_5bjr','kodeorg,bjr,basis,premibasis,premilebihbasis',
	    "kodeorg='".$param['kodeorg']."' and tahunproduksi = '".$tahuntahuntahun."'");
//        echo "error:".$query." ".$bjr;
	$res = fetchData($query);
	if(!empty($res)) {
            $bjr=$res[0]['bjr'];
            $basis=$res[0]['basis'];
            $premibasis=$res[0]['premibasis'];            
            $premilebihbasis=$res[0]['premilebihbasis']; 
//            if($regional!='KALTIM'and($lokasitugas!='SOGE' and $lokasitugas!='SENE')){
            if($regional!='KALTIM'){
                $premilebihbasis=$premilebihbasis*1.5;
            }                        
            $hasil3=$hasilhasilhasil*$bjr;
	} else {
            $bjr=0;
            $basis=0;
            $premibasis=0;
            $premilebihbasis=0;
	    $hasil3=0;
	}
        
        // dah ga dipake, pindah ke atas feb 10, 2017 tatang
//        // ambil basis yang paling kecil
//        $query = "SELECT bjr, afdeling, basis, premibasis, premilebihbasis
//            FROM ".$dbname.".`kebun_5basispanen2`
//            WHERE afdeling LIKE '".$afdelingafdelingafdeling."' order by bjr asc limit 1
//            ";
//	$res = fetchData($query);
//	if(!empty($res)) {
//            $bjrpalingkecil=$res[0]['bjr'];
//	}
//        // ambil basis yang paling besar
//        $query = "SELECT bjr, afdeling, basis, premibasis, premilebihbasis
//            FROM ".$dbname.".`kebun_5basispanen2`
//            WHERE afdeling LIKE '".$afdelingafdelingafdeling."' order by bjr desc limit 1
//            ";
//	$res = fetchData($query);
//	if(!empty($res)) {
//            $bjrpalingbesar=$res[0]['bjr'];          
//	}
//        
//        $bjr2=$bjr;
//        if($bjr<$bjrpalingkecil)$bjr2=$bjrpalingkecil;
//        if($bjr>$bjrpalingbesar)$bjr2=$bjrpalingbesar;        
//        
//        // ambil basis berdasarkan bjr + afdeling
//        $query = "SELECT afdeling, basis, premibasis, premilebihbasis
//            FROM ".$dbname.".`kebun_5basispanen2`
//            WHERE afdeling LIKE '".$afdelingafdelingafdeling."' and bjr = ".round($bjr2,2)."
//            ";
//        
//	$res = fetchData($query);
//	if(!empty($res)) {
//            $basis=$res[0]['basis'];
//            $premibasis=$res[0]['premibasis'];            
//            $premilebihbasis=$res[0]['premilebihbasis']; 
//            if($regional!='KALTIM'){
//                $premilebihbasis=$premilebihbasis*1.5;
//            }
//	}
        $hasil33=$hasilhasilhasil*$premilebihbasis;
        
        // itung premi basis (kalo 2x basis, dapet 2x... dst)
        @$kalibasis=floor($hasilhasilhasil/$basis);        
        $premibasis=$premibasis*$kalibasis;     
//        if($regional!='KALTIM'and($lokasitugas!='SOGE' and $lokasitugas!='SENE')){
        if($regional!='KALTIM'){
                $premibasis=0;
            }
//        $premibasis=0;
        // hasil kerja ## premi lebih basis ## basis ## premi basis
        echo $hasil3.'##'.$hasil33.'##'.$basis.'##'.$premibasis.'##'.$upahharian;
	break;          
    case 'updUpah':
	$firstKary = $param['nik'];
	$qUMR = selectQuery($dbname,'sdm_5gajipokok','sum(jumlah) as nilai',
	    "karyawanid=".$firstKary." and tahun=".$param['tahun']." and idkomponen in (1,31)");
	$Umr = fetchData($qUMR);
        $upahharian=round($Umr[0]['nilai']/25);
        $luaspanen=$param['luaspanen'];
        $hasilkerja=$param['hasilkerja'];
        $basis=$param['basis'];
        
        // cek yang bisa panen berdasarkan taksasi
        $query = "SELECT *
            FROM ".$dbname.".`kebun_taksasi` a
            WHERE a.`tanggal` = '".tanggalsystem($param['tanggal'])."' and a.`blok` = '".$param['kodeorg']."' and `posting` = 1
            ";
        $qDetail=mysql_query($query) or die(mysql_error($conn));
        while($rDetail=mysql_fetch_assoc($qDetail))
        {
            $luastaksasi=($rDetail['hasisa']+$rDetail['haesok']);
//            $hktaksasi=$rDetail['hk'];
            $jjgmasak=$rDetail['jjgmasak'];
            $jjgoutput=$rDetail['jjgoutput'];
            
            $akp=$rDetail['persenbuahmatang'];
        }
            @$hktaksasi=ceil($jjgmasak/$jjgoutput);
        
                // tambahan tatang
                $luasoutput=$luastaksasi/$hktaksasi;                
        
  $sorg="select kodeorg, jumlahpokok as pokokthnini, luasareaproduktif as hathnini from ".$dbname.".setup_blok where kodeorg ='".$param['kodeorg']."'";
    $qorg=mysql_query($sorg) or die(mysql_error($conn));
    while($rorg=mysql_fetch_assoc($qorg)){
        $pokok=$rorg['pokokthnini'];
        $luas=$rorg['hathnini'];
    }
    @$sph=round($pokok/$luas);          
    
            @$hktaksasi=ceil($jjgmasak/$jjgoutput);
        
        @$luasperhk=ceil($luastaksasi/$hktaksasi);
        if($luasperhk<=6){
            $yangbisapanen=$hktaksasi;            
        }else{
            $yangbisapanen=$luasperhk;
        }        
        
                @$capaibasis=$hasilkerja/$basis;        
        if(tanggalsystem($param['tanggal'])<'20140201'){
//            @$batasproporsi=round(0.8*$basis);
//            if(($capaibasis>=(0.8))or($luaspanen>=6)){ // luas lebih 6 ha lon dibuang
//                $upahpenalty=0;
//            }else{
//                @$upahpenalty=round($Umr[0]['nilai']/25*($capaibasis));
//                $upahpenalty=$upahharian-$upahpenalty;
//            }
//            
        }else{
            if($luasperhk <= 6){
    //            if(($capaibasis>=(0.8))or($luaspanen>=6)){ // luas lebih 6 ha lon dibuang
                if($capaibasis>=(0.8)){ // luas lebih 6 ha lon dibuang
                    $upahpenalty=0;
                }else{
                    @$upahpenalty=round($Umr[0]['nilai']/25*($capaibasis));
                    $upahpenalty=$upahharian-$upahpenalty;
                }
            }else{
                @$batasproporsi=$sph*6*$akp/100;
                if($hasilkerja>=($batasproporsi)){ // luas lebih 6 ha dibuang
                    $upahpenalty=0;
                }else{
                    @$upahpenalty=round($Umr[0]['nilai']/25*($capaibasis));
                    $upahpenalty=$upahharian-$upahpenalty;
                }
            }
            
            // remove temp 20200915
//            if($lokasitugas=='SOGE' or $lokasitugas=='SENE'){
////                if($luasoutput<4){ // akp tinggi
//                if($akp>$batasakp){ // akp tinggi
//                    $batasproporsi=round(2*$basis);
//                    if($hasilkerja<$batasproporsi){
//                        @$capaibasis=$hasilkerja/$batasproporsi;                    
//                        @$upahpenalty=round($Umr[0]['nilai']/25*($capaibasis));
//                        $upahpenalty=$upahharian-$upahpenalty;
//                        $premibasis=0;
//                        $premilebihbasis=0;
//                    }                    
//                    
//                }else{ // akp rendah
//                    if($luaspanen>4){
////                        $upahpenalty=0;
//                    }
//                }                
//            }            
            
        }       
        
	echo round($upahharian).'##'.round($upahpenalty);
	break;
        
    case 'updUpah2': // if($regional=='KALTIM')
	$firstKary = $param['nik'];
	$qUMR = selectQuery($dbname,'sdm_5gajipokok','sum(jumlah) as nilai',
	    "karyawanid=".$firstKary." and tahun=".$param['tahun']." and idkomponen in (1,31)");
	$Umr = fetchData($qUMR);
	echo $Umr[0]['nilai']/25;
	break;        
        
    default:
	break;
}
?>
