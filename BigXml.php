<?php

namespace App\NameDir;

use App\Model1;
use \XMLReader;


class NameClass {

/**
 * валидация xml файла
 * @param xmlFilename string - ссылка на файл
 */
public static function name_isValid($xmlFilename)
{
    $xml = new XMLReader();           
    $xml->open($xmlFilename);
    $xml->setParserProperty(XMLReader::VALIDATE, true);
    return $xml->isValid();            
}

/**
 * основной метод 
 * @param xmlFilename string - ссылка на файл
 * @param comp_name string - имя компании 
 */
public static function name2($xmlFilename,$comp_name)
{
  
    /**
     * получает имя для того чтобы 
     * поместить в xml файл сформированный
     */
    $company_name = $comp_name;
   
    
    $reader = new XMLReader();

        $reader->open($xmlFilename);
        
        $total_big_xml=[];
        

        /**
         * массив имен тэгов которые требуется спарсить 
         * для далнейшей обработки
         */
        $tags_xml = ['name1','name2','name3'];


        while($reader->read()){
      
            /**
             * если нода в файле является элементом
             * то обрабатывать ее
             */
             if($reader->nodeType == XMLReader::ELEMENT){
                
                $tag_name = $reader->localName;
                
                
                /**
                 * получает данные узла по имени
                 * которое есть в массиве tags_xml
                 * и добавляет эту информацию в массив общий под ключом имени тэга
                 */
                if(in_array($tag_name,$tags_xml)){

                    $total_big_xml[$tag_name][] = self::getNodeXmlData($reader,$tag_name);
                }
           
         }else{continue;}
       
    }

    $reader->close();

    /**
     * после получения общего массива с информацией
     * вызывается метод который формирует временные файлы  
     * которые будут обрабатываться .....где то....
     * и вовзвращает туда  массив с сылками на сформированные файлы
     * которые находятся во временной директории приложения пока они обрабатываются
     * после обработки директория удалятся вместе с этими файлами
     * @param total_big_xml array - общий массив данных из xml пользователя
     * @param company_name string - имя компании
     */
   $arr_urls_small_xml = self::makeTempSmallXmlFiles($total_big_xml,$company_name);
    

    return $arr_urls_small_xml;
    
}

/**
 * метод возвращает данные узла заданного имени тэга
 * передается аргументом reader(каретка) и имя узла
 * @param reader resource
 * @param tag_name string - имя тэга
 */
public static function getNodeXmlData($reader,$tag_name)
{
   return trim($reader->readOuterXml($tag_name));
}

/**
* удаляет директорию с временными файлами
*@param path string - путь к директории с временными файлами
*/
public static function delTmpXmlFilesAndDir($path)
{
    
     $urlsDir = public_path($path);
     $dir=$urlsDir;
     array_map('unlink', glob("$dir/*.*"));
     rmdir($dir);
}

/**
 * создание директории для файлов временных
 * (мелкие части одного большого файла xml) 
 * @param path string - путь к директории с временными файлами
 */
public static function makeTempFiles($path)
{
    
    if( is_dir(public_path($path)) ){
        
      self::delTmpXmlFilesAndDir($path);  
    }
    
    $dir = public_path($path);       
     mkdir($dir, 0777, true);
    
}

/**
 * основной рабочий метод который формирует
 * мелкие файлы xml для дальнейшего парсинга
 * и возвращает массив ссылок на них
 * @param arr array - основной массив информации из файла пользователя
 * @param company_name string - имя компании
 */
public static function makeTempSmallXmlFiles($arr,$company_name)
{
    
    $company = $company_name;
    

    /**
     * массив для хранения ссылок на
     * сформированные временные файлы
     * (мелкие куски большего файла от пользователя)
     */
    $url_for_pars = [];
    
    /**
     * массив содержащий все товары (полученный обработкой тэга offer в xml от пользователя)
     * разбивается на мелкие массивы (количество в параметрах указывается)
     * для того чтобы в одном файле для парсинга не содержался весь объем данных
     */
    $colect = $arr['name3'];
    $chunks = array_chunk($colect,5000);

    /**
     * создание директории для временных
     * файлов 
     */
    self::makeTempFiles('example/dir/');
    
    /**
     * формируется дата для файла в атрибут date
     * тэга yml_catalog в xml файл
     */
    $dateToday = self::makeDataForXml();
    

    /**
     * на каждой итерации цикла формируется
     * файл xml содержащий ранее определенное
     * количество товаров тэга name3 
     * (ранее был разбит массив на подмассивы chunks)
     */
    foreach($chunks as $off){

        //формируется имя файла
        $name = sha1(date('YmdHis') . str_random(30));
        $resize_name = $name . str_random(2) . '.xml';

    //формируется путь к файлу в приложении
        $tempPath = 'example/dir/'.$resize_name;
        $path = public_path($tempPath);
        
        //создание нового xml файла 
        $filename = fopen($path,"w+");
        
        fwrite($filename, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
        fwrite($filename, "<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">\n");
        fwrite($filename, "<yml_catalog date=\"{$dateToday}\">\n");
        fwrite($filename, "<shop>\n");
        fwrite($filename, "<name>NameCompany</name>\n");
        fwrite($filename, "<company>{$company}</company>\n");
        fwrite($filename, "<url>https://path...</url>\n");
    
        //рисуется отдел name1
        foreach($arr['name1'] as $curr){
            
            fwrite($filename, $curr);
        }

        //рисуется отдел name2
        foreach($arr['name2'] as $cat){
            
            fwrite($filename, $cat);
        }
        

        fwrite($filename, "<items>\n");

        //рисуется отдел с товарами
        foreach($off as $offer){
        
        fwrite($filename, $offer);
            
        }
        
        fwrite($filename, "</items>\n");

        fwrite($filename,"</shop>\n");
        fwrite($filename, "</yml_catalog>");
        fclose($filename);


        $url = 'http://path....'.$tempPath;

        $url_for_pars[] =$url;

        /**
         * (один из вариантов -
         * если требуется возможно здесь 
         * сделать запись в БД ссылки на файл
         * чтобы потом его спарсить) или после цикла
         * весь массив сразу записать в БД
         */

    }//окончание цикла формирования файла

    
    return $url_for_pars;
    
}

public static function makeDataForXml()
{
    $dateToday = time() + (7 * 24 * 60 * 60);
    $dateToday = date('Y-m-d H:i:s');
    return $dateToday;
}

}        