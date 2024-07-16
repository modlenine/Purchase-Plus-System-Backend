<?php
class upload_fn{
    private $ci;
    function __construct()
    {
        $this->ci =&get_instance();
        date_default_timezone_set("Asia/Bangkok");
    }

    function uploadci()
    {
        return $this->ci;
    }
}

function uploadfn()
{
    $obj = new upload_fn();
    return $obj->uploadci();
}

function resize($width, $targetFile, $originalFile ) 
{
    $info = getimagesize($originalFile); 
    $mime = $info['mime']; 
 
    switch ($mime) { 
            case 'image/jpeg':
                    header('Content-Type: image/jpeg');
                    $image_create_func = 'imagecreatefromjpeg'; 
                    $image_save_func = 'imagejpeg'; 
                    $filename_type = 'jpg'; 
                    break; 
 
            case 'image/png': 
                    header('Content-Type: image/png');
                    $image_create_func = 'imagecreatefrompng'; 
                    $image_save_func = 'imagepng'; 
                    $filename_type = 'png'; 
                    break; 
 
            case 'image/gif':
                    header('Content-Type: image/gif');
                    $image_create_func = 'imagecreatefromgif'; 
                    $image_save_func = 'imagegif'; 
                    $filename_type = 'gif'; 
                    break; 
 
            default:  
                    throw error_log('Unknown image type.'); 
    } 
 

    list($width_orig, $height_orig) = getimagesize($originalFile); 
    $height = (int) (($width / $width_orig) * $height_orig); 
    $image_p = imagecreatetruecolor($width, $height);
    $image   = $image_create_func($originalFile);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
 
    
    // $image_save_func($tmp, "$targetFile.$new_image_ext"); 
    //Fix Orientation
    $exif = exif_read_data($originalFile);
    if ($exif && isset($exif['Orientation']))
    {
        $orientation = $exif['Orientation'];
        switch($orientation) {
            case 3:
                $image_p = imagerotate($image_p, 180, 0);
                break;
            case 6:
                $image_p = imagerotate($image_p, -90, 0);
                break;
            case 8:
                $image_p = imagerotate($image_p, 90, 0);
                break;
        }
    }
    // Output
    $image_save_func($image_p, "$targetFile.$filename_type", 90);




}



function uploadFile($fileInput , $formcode)
{
    // Upload file Zone
    // Check folder ว่ามีอยู่หรือไม่
    $yearNow = date("Y");
    $dateNow = date("Y-m-d");
    $imagePath = "uploads/files/".$yearNow."/".$dateNow."/";
    // $paths = 'uploads\images';
    $runningCode = getRuningCode();
    $fileno = 1;



    $url = $_SERVER['HTTP_HOST'];
    if($url == "localhost"){
        $paths = 'uploads\files';
        if(!file_exists($paths."\\".$yearNow)){
            mkdir($paths."\\".$yearNow , 0755 , true);
        }
        if(!file_exists($paths."\\".$yearNow."\\".$dateNow)){
            mkdir($paths."\\".$yearNow."\\".$dateNow , 0755 , true);
        }
    }else{
        $paths = 'uploads/files';
        if(!file_exists($paths."/".$yearNow)){
            mkdir($paths."/".$yearNow , 0755 , true);
        }
        if(!file_exists($paths."/".$yearNow."/".$dateNow)){
            mkdir($paths."/".$yearNow."/".$dateNow , 0755 , true);
        }
    }

   
    $file_name = $_FILES[$fileInput]['name'];

    foreach($file_name as $key => $value){

        if ($_FILES[$fileInput]['tmp_name'][$key] != "") {

            $path_parts = pathinfo($value);

            if($path_parts['extension'] == "jpeg"){
                $filename_type = "jpg";
            }else{
                $filename_type = $path_parts['extension'];
            }
            
            $file_name_date = substr_replace($value,  $formcode."-". $fileno."-".$runningCode.".". $filename_type, 0);

            $file_name_s = substr_replace($value,  $formcode."-". $fileno."-".$runningCode , 0);
            // Upload file
            $file_tmp = $_FILES[$fileInput]['tmp_name'][$key];



            if($path_parts['extension'] != "pdf" && $path_parts['extension'] != "PDF" && $path_parts['extension'] != "png" && $path_parts['extension'] != "PNG"){
                $newWidth = 1000;
                resize($newWidth, "uploads/files/".$yearNow."/".$dateNow."/".$file_name_s, $file_tmp);
                // move_uploaded_file($file_tmp, "upload/images/" . $file_name_date);
                // correctImageOrientation($file_tmp);
            }else{
                move_uploaded_file($file_tmp, "uploads/files/".$yearNow."/".$dateNow."/". $file_name_date);
                $uploadFile = "uploads/files/".$yearNow."/".$dateNow."/". $file_name_date;
                chmod($uploadFile, 0755);
            }

            // Save Data Image to Database
            $arSaveDataImage = array(
                "f_formno" => $formcode,
                "f_name" => $file_name_date,
                "f_path" => $imagePath,
                "f_datetime" => date("Y-m-d H:i:s")
            );
            uploadfn()->db->insert("files" , $arSaveDataImage);

        } 

        $fileno++;
    }
    // Upload file Zone
}











?>