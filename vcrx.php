<?php
header('Access-Control-Allow-Origin: *'); 
require __DIR__ . '/vendor/autoload.php';

function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Drive API PHP Quickstart');
    $client->setScopes([
    	"https://www.googleapis.com/auth/drive",
        "https://www.googleapis.com/auth/drive.file",
        "https://www.googleapis.com/auth/spreadsheets"
    ]);
    $client->setAuthConfig('credentials_vcrx.json');
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = 'token_vcrx0.0.1.json';
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            throw new Exception(join(', ', $accessToken));
        }

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

$client = getClient();
$rs = array();
switch ($_GET['action']) {
    case 'create-gdoc':
        //DaoNC
        $titleFile = isset($_GET['title'])?"VCRXUNIdoc: ".$_GET['title']:"VCRXUNIdoc: template";
        $serviceDrive = new Google_Service_Drive($client);
        $optParams = array(
            'pageSize' => 100,
            'fields'   => 'nextPageToken, files(id, name)',
            'q'        => 'name = "'.$titleFile.'"'
        );
        $listFiles = $serviceDrive->files->listFiles($optParams);
        $rs = array();
        if (!(count($listFiles->getFiles()) == 0)) {
            foreach ($listFiles->getFiles() as $file) {
                $rs = array(
                    "title" => $file->getName(),
                    "id"    => $file->getId()
                );
            }
        }else {
            $fileMetadata = new Google_Service_Drive_DriveFile(array(
                'name'     => $titleFile,
                'mimeType' => 'application/vnd.google-apps.document'));
            $file = $serviceDrive->files->create($fileMetadata, array('fields' => 'id'));
            $rs = array(
                "title" => $fileMetadata->name,
                "id"    => $file->getId()
            );
        }
        $newPermission  = new Google_Service_Drive_Permission();
        $newPermission->setType('anyone');
        $newPermission->setRole('reader');
        $optParams = array('sendNotificationEmail' => false);
        $serviceDrive->permissions->create( $rs['id'], $newPermission, $optParams );
        break;
    case 'create-room':
        $titleFile = isset($_GET['title'])?"VCRXUNI: ".$_GET['title']:"VCRXUNI: template";
        $serviceDrive = new Google_Service_Drive($client);
        $optParams = array(
          'pageSize' => 100,
          'fields' => 'nextPageToken, files(id, name)',
          'q' => 'name = "'.$titleFile.'"'
        );
        $listFiles = $serviceDrive->files->listFiles($optParams);
        if (!(count($listFiles->getFiles()) == 0)) {
            foreach ($listFiles->getFiles() as $file) {
                $rs = array(
                    "title" => $file->getName(),
                    "id"    => $file->getId()
                );
            }
        }else{
            //Tạo file mới
            $serviceSheet    = new Google_Service_Sheets($client);
            $requestBody = new Google_Service_Sheets_Spreadsheet();
            $requestBody->properties = ["title" => $titleFile];
            $requestBody->sheets = ["properties" => ["title" => $titleFile]];
            $sheet = $serviceSheet->spreadsheets->create($requestBody);
            $rs = array(
                "title" => $sheet->properties->title,
                "id"    => $sheet->spreadsheetId
            );
        }
        //Share all quyền view
        $newPermission  = new Google_Service_Drive_Permission();
        $newPermission->setType('anyone');
        $newPermission->setRole('reader');
        $optParams = array('sendNotificationEmail' => false);
        $serviceDrive->permissions->create( $rs['id'], $newPermission, $optParams );
        break;
    case 'share-file':
        $id     = isset($_GET['id'])?$_GET['id']:"19W7CK0bEufVZ7W3vJhca0Q7tzlvsjvuqmim0lR42KJs";
        $email  = isset($_GET['email'])?$_GET['email']:"anhlh011190@gmail.com";
        $role   = isset($_GET['role'])?$_GET['role']:"reader";
        $serviceDrive = new Google_Service_Drive($client);

        $newPermission  = new Google_Service_Drive_Permission();
        $newPermission->setEmailAddress($email);
        $newPermission->setType('user');
        $newPermission->setRole($role);
        $optParams = array('sendNotificationEmail' => false);
        $rs = $serviceDrive->permissions->create( $id, $newPermission, $optParams );
        break;
    case 'unshare-file':
        $id     = isset($_GET['id'])?$_GET['id']:"19W7CK0bEufVZ7W3vJhca0Q7tzlvsjvuqmim0lR42KJs";
        $iduser = isset($_GET['iduser'])?$_GET['iduser']:"03906984693744639385";
        $email  = isset($_GET['email'])?$_GET['email']:"anhlh011190@gmail.com";
        $role   = isset($_GET['role'])?$_GET['role']:"reader";
        $serviceDrive = new Google_Service_Drive($client);
        $rs = $serviceDrive->permissions->delete( $id, $iduser );
        if($role != "reader"){
            $newPermission  = new Google_Service_Drive_Permission();
            $newPermission->setEmailAddress($email);
            $newPermission->setType('user');
            $newPermission->setRole($role);
            $optParams = array('sendNotificationEmail' => false);
            $serviceDrive->permissions->create( $id, $newPermission, $optParams );
        }
        break;
    default:
        break;
}
echo json_encode($rs);	