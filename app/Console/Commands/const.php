<?php

namespace App\Console\Commands;
require_once('rakumachi.php');

class Prefecture{

    const HOKKAIDO = 1;
    const AOMORI = 2;
    const TOKUSHIMA = 36;

    public $prefecture;
    public $option;
    public $pref_num;
    public $option_file;

    public function getPrefNum(){
        if($this->prefecture == "北海道"){
            $this->pref_num = self::HOKKAIDO;
            $this->option_file = 'hokkaido';
            $this->option = 'pref';
        }elseif($this->prefecture == "青森"){
            $this->pref_num = self::AOMORI;
            $this->option_file = 'aomori';
            $this->option = 'pref';
        }elseif($this->prefecture == "徳島"){
            $this->pref_num = self::TOKUSHIMA;
            $this->option_file = 'tokushima';
            $this->option = 'pref';
        }else{
            echo '--prefオプションの入力に誤りがあります。';
            return;
        }
    }
}


class Types{

    const oneCondominium = '&dim[]=1001';
    const oneApartment = '&dim[]=1002';

    public $props;
    public $prop_num;
    public $option;
    public $option_file;

    public function getPropNum(){

        if($this->props == "1棟マンション"){
            $this->prop_num = Types::oneCondominium;
            $this->option = 'prop';
            $this->option_file = 'oneCondominium';
        }elseif($this->props == "1棟アパート"){
            $this->prop_num = Types::oneApartment;
            $this->option = 'prop';
            $this->option_file = 'oneApartment';
        }elseif($this->props == ''){
            $this->prop_num = '';
            $this->option = 'none';
            $this->option_file = 'all';
        }else{
            echo '--propオプションの入力に誤りがあります。';
            return;
        }
    }
    
}