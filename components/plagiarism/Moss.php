<?php
/*
The MIT License (MIT)

Copyright (c) 2014 Philipp Helo Rehs

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace app\components\plagiarism;

use app\exceptions\PlagiarismServiceException;
use \Exception;
use Yii;

/**
 * @author Philipp Helo Rehs <P.Rehs@gmx.net>
 * @author Gergő Prepok <g.prepok@hotmail.com>
 * @author Máté Cserép <mcserep@inf.elte.hu>
 * @version 1.1
 * @moss-version 2.0
 */
class Moss {
    private $language_extensions = [
        "c" => ["c", "h"],
        "cc" => ["cc", "cpp", "cxx", "c++", "h", "hpp", "hxx", "h++"],
        "java" => ["java", "jar", "class"],
        "ml" => ["ml"],
        "pascal" => ["pp, pas, inc"],
        "ada" => ["adb", "ads"],
        "lisp" => ["lisp"],
        "scheme" => ["scm", "ss"],
        "haskell" => ["hs", "lhs"],
        "fortran" => ["f", "f90", "f95", "for"],
        //"ascii" => ["txt", "md"],
        "vhdl" => ["vhdl"],
        "perl" => ["pl", "pm", "t", "pod"],
        "matlab" => ["m"],
        "python" => ["py", "pyc", "pyd", "pyo", "pyw", "pyz"],
        "mips" => ["asm", "s"],
        "prolog" => ["pl", "pro", "P"],
        "spice" => ["cir"],
        "vb" => ["vb"],
        "csharp" => ["cs"],
        "modula2" => ["mod", "m2", "def", "mi", "md"],
        "a8086" => ["asm"],
        "javascript" => ["js"],
        "plsql" => ["sql"],
        "verilog" => ["v"],
    ];
    private $extension_languages = array();

    private $allowed_languages = array();
    private $allowed_extensions = array();

    private $options = array();
    private $basefiles = array();
    private $files = array();
    private $server;
    private $port;
    private $userid;

    /**
     * @param int  	  $userid
     * @param string  $server
     * @param integer $port
     */
    public function __construct($userid, $server = "moss.stanford.edu", $port = 7690){
        $this->allowed_languages = array_keys($this->language_extensions);
        foreach ($this->language_extensions as $lang => $extensions) {
            $this->allowed_extensions = array_merge($this->allowed_extensions, $extensions);

            foreach ($extensions as $ext) {
                if (!isset($this->extension_languages[$ext]))
                    $this->extension_languages[$ext] = [];
                array_push($this->extension_languages[$ext], $lang);
            }
        }
        $this->allowed_extensions = array_unique($this->allowed_extensions);

        $this->options['m'] = 10;
        $this->options['d'] = 0;
        $this->options['n'] = 250;
        $this->options['x'] = 0;
        $this->options['c'] = "";
        $this->options['l'] = "c";
        $this->server = $server;
        $this->port = $port;
        $this->userid = $userid;
    }

    /**
     * set the language of the source files
     * @param string $lang
     */
    public function setLanguage($lang){
        if(in_array($lang, $this->allowed_languages)){
            $this->options['l'] = $lang;
            return true;
        }
        else{
            throw new Exception("Unsupported language", 1);
        }
    }

    /**
     * get a list with all supported languages
     * @return array
     */
    public function getAllowedLanguages(){
        return $this->allowed_languages;
    }

    /**
     * get a list with all supported language extensions
     * @return array
     */
    public function getAllowedExtensions() {
        return $this->allowed_extensions;
    }

    /**
     * get a list of supported extensions for a language
     * @return array
     */
    public function getLanguageExtensions($lang) {
        if (in_array($lang, $this->allowed_languages)) {
            return $this->language_extensions[$lang];
        }
        else{
            throw new Exception("Unsupported language", 1);
        }
    }

    /**
     * get a list of supported languages for an extension
     * @return array
     */
    public function getExtensionLanguages($ext) {
        if (in_array($ext, $this->allowed_extensions)) {
            return $this->extension_languages[$ext];
        }
        else{
            throw new Exception("Unsupported extension", 1);
        }
    }

    /**
     * Enable Directory-Mode
     * @see -d in MOSS-Documentation
     * @param bool $enabled
     */
    public function setDirectoryMode($enabled){
        if(is_bool($enabled)){
            $this->options['d'] = (int)$enabled;
            return true;
        }
        else{
            throw new Exception("DirectoryMode must be a boolean", 2);
        }
    }

    /**
     * Add a basefile
     * @see -b in MOSS-Documentation
     * @param string $file
     */
    public function addBaseFile($file){
        if(file_exists($file) && is_readable($file)){
            $this->basefiles[] = $file;
            return true;
        }
        else{
            throw new Exception("Can't find or read the basefile (".$file.")", 3);
        }
    }

    /**
     * Occurences of a string over the limit will be ignored
     * @see -m in MOSS-Documentation
     * @param int $limit
     */
    public function setIgnoreLimit($limit){
        if(is_int($limit) && $limit > 1){
            $this->options['m'] = (int)$limit;
            return true;
        }
        else{
            throw new Exception("The limit needs to be greater than 1", 4);
        }
    }

    /**
     * Set the comment for the request
     * @see -s in MOSS-Documentation
     * @param string $comment
     */
    public function setCommentString($comment){
        $this->options['c'] = $comment;
        return true;
    }

    /**
     * Set the number of results
     * @see -n in MOSS-Documentation
     * @param int $limit
     */
    public function setResultLimit($limit){
        if(is_int($limit) && $limit > 1){
            $this->options['n'] = (int)$limit;
            return true;
        }
        else{
            throw new Exception("The limit needs to be greater than 1", 5);
        }
    }

    /**
     * Enable the Experimental Server
     * @see -x in MOSS-Documentation
     * @param bool $enabled
     */
    public function setExperimentalServer($enabled){
        if(is_bool($enabled)){
            $this->options['x'] = (int)$enabled;
            return true;
        }
        else{
            throw new Exception("Needs to be a boolean", 6);
        }
    }

    /**
     * Add a file to the request
     * @param string $file
     */
    public function addFile($file){
        if(file_exists($file) && is_readable($file)){
            $this->files[] = $file;
            return true;
        }
        else{
            throw new Exception("Can't find or read the file (".$file.")", 7);
        }
    }

    /**
     * Add files by a wildcard
     * @example addByWildcard("/files/*.c")
     * @param string $path
     */
    public function addByWildcard($path){
        foreach(glob($path) as $file){
            $this->addFile($file);
        }
    }

    /**
     * Send the request to the server and wait for the response
     * @return string
     */
    public function send(){
        try {
            $socket = fsockopen($this->server,$this->port, $errno, $errstr);
        } catch (\ErrorException $e) {
            Yii::error($e->getMessage(), __METHOD__);
            throw new PlagiarismServiceException(Yii::t('app','Moss server currently unavailable'));
        }

        if(!$socket){
            throw new Exception("Socket-Error: ".$errstr." (".$errno.")", 8);
        }
        else{
            fwrite($socket, "moss ".$this->userid."\n");
            fwrite($socket, "directory ".$this->options['d']."\n");
            fwrite($socket, "X ".$this->options['x']."\n");
            fwrite($socket, "maxmatches ".$this->options['m']."\n");
            fwrite($socket, "show ".$this->options['n']."\n");
            //Language Check
            fwrite($socket, "language ".$this->options['l']."\n");
            $read = trim(fgets($socket));
            if($read == "no"){
                fwrite($socket, "end\n");
                fclose($socket);
                throw new Exception("Unsupported language", 1);
            }
            foreach($this->basefiles as $bfile){
                $this->uploadFile($socket, $bfile, 0);
            }
            $i = 1;
            foreach($this->files as $file){
                $this->uploadFile($socket, $file, $i);
                $i++;
            }
            fwrite($socket, "query 0 ".$this->options['c']."\n");
            $read = fgets($socket);
            fwrite($socket, "end\n");
            fclose($socket);
            return $read;
        }
    }

    /**
     * Upload a file to the server
     * @param resource $handle A handle from fsockopen
     * @param string $file   The Path of the file
     * @param int $id     0 = Basefile, incrementing for every normal file
     * @return void
     */
    private function uploadFile($handle, $file, $id){
        $size = filesize($file);
        $file_name_fixed = str_replace(" ", "_", $file);
        fwrite($handle, "file ".$id." ".$this->options['l']." ".$size." ".$file_name_fixed."\n");
        fwrite($handle, file_get_contents($file));
    }
}
