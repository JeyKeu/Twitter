<?php

	   //remove RT & RT:
	  function remove_character($str){

    	$str = str_replace("RT:"," ",$str); 
		  $str = str_replace("RT "," ",$str); 
      $str = str_replace(","," ",$str); 
      $str = str_replace("!"," ",$str);
      $str = str_replace("."," ",$str);
      $str = str_replace("&"," ",$str);
      $str = str_replace(";"," ",$str);
      $str = str_replace(":"," ",$str);
      $str = str_replace("|"," ",$str);
      $str = str_replace("?"," ? ",$str);

		  return $str;
    }
   	//use USER to replace the user name in tweet
  	function replace_username($str,$tmp){

  	  if(substr($tmp,0,1)=="@"){
		  	$str = str_replace($tmp,"USER",$str); 
	 	  }

		  return $str;
  	}
  	//remove some extraordinary character
  	function remove_Special_character($str,$tmp){

  		if(substr($tmp,0,1)=="@"){
		  	$str = str_replace($tmp,"USER",$str); 
	   	}

		  return $str;
  	}
  	//extract all of emotions in tweet
  	function extract_emotions($str,$emotions_dictionary,$tmp,$emotions,$emotions_meaning){

  		if($emotions_dictionary[$tmp] != NULL){
        if($emotions == ""){
          $emotions = $tmp;
          $emotions_meaning = $emotions_dictionary[$tmp];
        }
        else{
           $emotions = $emotions." ".$tmp;
           $emotions_meaning = $emotions_meaning." ".$emotions_dictionary[$tmp];
        }
        $str = str_replace($tmp,"",$str);
  		}

      return $str;
  	}
    //determine the word is stop word or not
    function determine_stop_word($tmp,$stop_word){

       if($stop_word[strtolower($tmp)] == NULL)
        return 0;

      return 1;
    }
    //call tree tagger(POS)
    function POS_tagger($tmp){

        $str = trim($tmp);
        $string = "echo ".$str." > input.txt";
        $string = str_replace("'","''", $string);
        shell_exec($string);
        shell_exec("/Users/user/Documents/Code/tree-tagger/cmd/tree-tagger-english input.txt > output.txt");
        return shell_exec("cat output.txt"); 
    }
    //check if a word is an English word 0:no, 1;yes
    function check_word_english($tmp){

        $output = shell_exec("/usr/local/WordNet-3.0/bin/wn ".$tmp);
        //$output = trim($output);
        //echo $output.'</br>';
        $result = explode("\n", $output);
        for($i=0;$i<count($result);$i++){
            
          if($result[$i]=="")
            continue;

          //echo substr(trim($result[$i]), 0, 2).'</br>';
          if(substr(trim($result[$i]), 0, 2) != "No")
            return 1;
        }
        return 0;
    }
    //extract #tag and save it
    function extract_tag($str, $tmp, $tag){

      if(substr($tmp,0,1)=="#"){
        //echo $tmp.'</br>';
        $tag = $tmp;
        $str = str_replace($tmp,"",$str);
      }

      return $str;
    }
    //proecess repeated letter, if reapeated letter >=3 then replace with 2 same letter
    function repeated_letter($str){
      //echo $str.'</br>';
      $length =  strlen($str);
      $order = "";
          
      for($i=0;$i<$length;$i++){
        $count = 0;
        while(substr($str,$i,1) == substr($str,$i+1,1)){
          $count++;
          break;
        }        
        if($count>0 && !is_numeric($str)){
          $order = check_rule($str);
          //echo $str.'-'.$order.'</br>';
          break;
        }
      }
    }
    //check repeated letter rules
    function check_rule($str){
      $order = "";
      $previous = "";
      for($i=0;$i<strlen($str);$i++){
        if($previous!=substr($str,$i,1)){
          $order = $order.substr($str,$i,1);
        }
        $previous = substr($str,$i,1);
      }
      return $order;
    }
      
    //try to define the meaning of non_english
    function define_non_English_word($prefix){
      // load Zend classes
      require_once 'Zend/Loader.php';
      Zend_Loader::loadClass('Zend_Rest_Client');

      //echo $prefix.'</br>';
      try {
        // initialize REST client
        $wikipedia = new Zend_Rest_Client('http://en.wikipedia.org/w/api.php');

        // set query parameters
        $wikipedia->action('query');
        $wikipedia->list('allcategories');
        $wikipedia->acprefix($prefix);
        $wikipedia->format('xml');

        // perform request
        // iterate over XML result set
        $result = $wikipedia->get();
      } catch (Exception $e) {
        die('ERROR: ' . $e->getMessage());
      }

      //echo "----------</br>";
      //foreach ($result->query->allcategories->c as $c);
        //echo $c.'</br>';
      //endforeach;
      //echo "----------</br>";*/
      $string = $result->query->allcategories->c;
    
      if(trim($string) != NULL)
        return 1;
        
      return 0;

    }
    function tagging($postag,$result){
      $tmp = explode("\t", $postag);
      //set Word
      $result['Word'] = $tmp[0];
      //set tagger
      $result['POS'] = $tmp[1];
    }

    function process_slang($result,$db_slang){
     
      $term = trim($result['Word']);
      //echo $term.'</br>';
      $term = str_replace("'","''",$term);    
      if (preg_match("/^[a-zA-Z]/",$result['Word'])){
          $table = strtolower(substr(trim($result['Word']), 0, 1));
      }
      else{
          $table = "other";
      }
      //echo $table.'</br>';
      //$sql = "SELECT meaning FROM $table WHERE slang = '{$term}'";
      //echo $sql.'</br>';
      $db_slang->query("SELECT meaning FROM $table WHERE slang = '{$term}' ");
      //the result of searching slang dictionary.
      $str = $db_slang->fetch_array();
      if($str['meaning']!=null){
            //echo $term.' '.$str['meaning'].'</br>';
            $result['Word'] = $str['meaning'];
            $result['English'] = "EN";
      }
    }

?>