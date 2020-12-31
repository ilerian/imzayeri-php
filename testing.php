<?php

    // Swagger dosyalarını composer'ın autoload.php dosyası ile yüklüyoruz
    require_once("imzayeri-php/vendor/autoload.php");

    // Kullanacağımız API hizmetlerini belirtiyoruz.
    // Swagger\Client\Api dizini, bütün API endpointlerinin olduğu dizin.
    use Swagger\Client\Api\JobsApi;
    use Swagger\Client\Api\RecipientsApi;
    use Swagger\Client\Api\DocumentsApi;
    
    // $config değişkeni önemli, setHost ile istek atılacak domain belirleniyor.
    // $config değişkeni set edilmezse call yapılamaz.
    $config = new \Swagger\Client\Configuration();
    $config->setHost("https://securewebserver.net/jetty/imytest/rest");
    //$config->setHost("https://localhost/agree/rest");

    // API'ye istek atmak için sistemin kullanıcıya verdiği accessToken kullanılması zorunludur. 
    $accessToken = "eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJhdXRoMHw1ZmE4ZjRkNjYyMDRhZTAwNjhhYzQ4OGYiLCJuYmYiOjE2MDkzMzMyMTQsImlzcyI6ImltemF5ZXJpIiwiZXhwIjoxNjEwODA0NDQzLCJpYXQiOjE2MDkzMzMyMTQsImp0aSI6ImVjZmYyNzg3LTFmMzctNDU0MS04N2JhLTcwMzExNTgzMmQzMSJ9.ZecywNROuahIa0zdKYsS2zNbLf88zqTnV8HJx8VtfFrpQEncLPXmBsSC2HQIbWlm2Wk1X_hejwyIb4Mxqb6UpUqnq4RSOJCtXH38G4ExR5F1ow6R4IPi4wMiAd-rHxyKMhq67oIr75zlZxQDfi4mqn-gdMbBXwFKRYJgNvNgmnun6Vi8XKtYhhssMQmhOhh8iCWr6kr0XIVcezyVjNtuY3qyXfOzh-QCP_g66sm3h2ykiFw4eMBFgkiBBfH1mRVOYjHK74Jw2lVDlCPWCRpOTmlaCp748HzNZ-xmE6F0-xEo12Jrh60ABSPH-w7xG9ikw2Hetl8ZzEcncc4cK1IbWw";

    // API hizmetlerini $config değişkeni ile initialize ediyoruz.
    $jobApi = new JobsApi(null,$config,null);
    $recipientApi = new RecipientsApi(null,$config,null);
    $documentApi = new DocumentsApi(null,$config,null);

    // Döküman yüklemek için dosyanın base64 formatına çevirilmesi gerekiyor.
    // Burada statik bir path verilmiş. Kullanıcının $path değişkenini belirlemesi gerekmektedir. Relative path olarak yazılmalıdır.
    $path = 'sample.pdf';
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:application/' . $type . ';base64,' . base64_encode($data);

    // Sistemde her yükleme işlemi bir Job ile birlikte kullanılır.
    // Bir döküman ile ilişkilendirilmeyen bir job kullanışsızdır ve internet sitesinde gözükmez.
    // İlk parametre işin türünü belirtir. "SELF" yükleyenin kendisi, "ME_OTHERS" yükleyen ve eklenen diğer alıcıların imzalayabileceği bir dökümandır.
    // İkinci parametre işin ismini belirtir. Döküman ile ilişkilendirildiğinde, otomatik olarak dosyanın adını alacağı için "No Name" olarak bırakılması tercih edilir.
    // Üçüncü parametre accessToken.
    $jobCreateResponse = $jobApi->jobsPost("ME_OTHERS","No Name",$accessToken);
    
    // Gelen isteği ekrana yazdırmak için kullanılan kod parçası.
    // echo "<pre>"; 
    // var_export($jobCreateResponse);
    // echo "</pre>";

    // jobsPost isteğinden dönen objeden, üretilen Job'ub ID'sini çekiyoruz.
    $responseJobId = $jobCreateResponse["id"];
    
    // Dosyayı yüklemek için kullanılan API call'u
    // İlk parametre dosyanın ismi belirtir.
    // İkinci parametre dosyanın base64 formatıdır.
    // Üçüncü Parametre dosyanın yüklendiği kaynaktır. "GDRIVE" yada "BOX" olabilir. GDRIVE tercih edilir.
    // Dördüncü Parametre accessToken
    // Beşinci Parametre JobID 
    $documentApi->jobsIdDocumentsCloudPost("DenemeDosyasi_".rand()."." .$type, $base64, "GDRIVE", $accessToken, $responseJobId);
    
    // Job'u ME_OTHERS durumuna geçirmek için kullanılan API call'u.
    // İlk parametre accessToken
    // İkinci Parametre JobID
    $jobApi->jobsIdActionsStartrequestsignaturePut($accessToken,$responseJobId);
    
    // Belirtilen Job'a recipient(alıcı) ekleme için kullanılan API Call'u
    // Birinci, İkinci ve Üçüncü parametreler sırasıyla İsim, Email ve TC Kimlik Numarasıdır.
    // Dördüncü Parametre eklenen recipient'ın rolünü belirtir ve SIGNER olmak zorundadır.
    // Beşinci Parametre recipient sıra numarasıdır.
    // Altıncı Parametre accessToken
    // Yedinci Parametre JobID 
    $recipientApi->jobsIdRecipientsPost("Arınç","arinc@ilerian.com","12345678901","SIGNER","OPTIONAL",1,$accessToken,$responseJobId);
    
    // Dökümanı imzaya yollayan ve alıcıların e-posta almasını sağlayan API call'u.
    // Birinci Parametre accessToken
    // İkinci Parametre JobID 
    // Üçüncü parametre dökümanın konusu
    // Dördüncü parametre dökümanın mesajıdır.
    $jobApi->jobsIdActionsFinishPut($accessToken,$responseJobId,"Konu","Mesaj");

?>
