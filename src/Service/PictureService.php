<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureService{
    private $params;

    public function __construct(ParameterBagInterface $params){
        $this->params = $params;
    }

    public function addPicture(
        UploadedFile $picture,
        ?string $folder = '',
        ?int $width = 250,
        ?int $height = 250
    ){
        # New name for the image file
        $file = md5(uniqid(rand(), true)).'.webp';

        # Get image's informations
        $picture_infos = getimagesize($picture);
        if($picture_infos === false){
            throw new Exception('Incorrect image format.');
        };

        # Checking image format
        switch($picture_infos['mime']){
            case 'image/png' : {
                $picture_source = imagecreatefrompng($picture);
                break;
            };
            case 'image/jpeg' : {
                $picture_source = imagecreatefromjpeg($picture);
                break;
            };
            case 'image/webp' : {
                $picture_source = imagecreatefromwebp($picture);
                break;
            };
            default : {
                throw new Exception('Incorrect image format.');
                break;
            };
        };

        # Reframing the image
        ## Getting image dimensions
        $image_width = $picture_infos[0];
        $image_height = $picture_infos[1];

        ## Checking image layout
        switch($image_width <=> $image_height){
            case -1 :{ //Portrait layout
                $square_size = $image_width;
                $src_x = 0;
                $src_y = ($image_height - $square_size) / 2;
                break;
            };
            case 0 :{ //Square layout
                $square_size = $image_width;
                $src_x = 0;
                $src_y = 0;
                break;
            };
            case 1 :{ //Landscape layout
                $square_size = $image_height;
                $src_x = ($image_width - $square_size) / 2;
                $src_y = 0;
                break;
            };
            default:{break;};
        };

        ## Creating a new blank image
        $resized_picture = imagecreatetruecolor($width, $height);
        imagecopyresampled(
            $resized_picture,
            $picture_source,
            0,
            0,
            $src_x,
            $src_y,
            $width,
            $height,
            $square_size,
            $square_size
        );
        $path = $this->params->get('uploads_directory').$folder;

        # Creating the images file it doesn't already exists
        if(!file_exists($path.'/mini/')){
            mkdir($path.'/mini/', 0755, true);
        };

        # Storing the resized image
        imagewebp(
            $resized_picture,
            $path.'/mini/'.$width.'x'.$height.'-'.$file
        );
        $picture->move($path.'/', $file);
        return $file;
    }

    public function deletePicture(
        string $file,
        ?string $folder = '',
        ?int $width = 250,
        ?int $height = 250
    ){
        $success = false;
        if($file !== 'default.webp'){
            $path = $this->params->get('uploads_directory').$folder;
            $mini = $path.'/mini/'.$width.'x'.$height.'-'.$file;
            $original = $path.'/'.$file;
            if(file_exists($mini)){unlink($mini);};
            if(file_exists($original)){unlink($original);};
            $success = true;
        };
        return $success;
    }
}