<?php
namespace Odin\Console;
/**
 * Created by PhpStorm.
 * User: Abyss
 * Date: 11.04.2016
 * Time: 22:10
 */
class ConsoleReader {

    protected $params_default = 'stty -g';
    protected $params_parsing = 'stty -icanon -echo min 1 time 0';
    public $prefix = '[Odin\ConsoleReader] > ';
    public $last_tab = '';
    public $last_complite = '';
    public $last_commands = [];
    public $last_commands_cursor = 0;


    protected $tab_count = 0;
    

    private function write_prefix(Coloredcli $colored_cli){
        if($this->prefix)
            return $colored_cli->getColoredString($this->prefix, "cyan");
    }

    public function read(){
        $colored_cli = new Coloredcli();
        $string = '';
        fwrite(STDOUT, $this->write_prefix($colored_cli));
        $_tty = shell_exec($this->params_default);
        shell_exec($this->params_parsing);
        while (true) {
            $char = fgetc(STDIN);

            if ($char === "\n") {
				fwrite(STDOUT, "\n");
                $this->last_commands[] = $string;
                $this->last_commands_cursor = count($this->last_commands) - 1;
                break;
            } else {
                if(ord($char) === 8){
                    if(strlen($string) > 0){
                        fwrite(STDOUT, "\x08 \x08");
                        $string = substr($string, 0, -1);
                    }elseif(!strlen($string)){
                        continue;
                    }else{
                        $string .= $char;
                    }
                    $this->tab_count = 0;
                    $this->last_tab = '';
                }elseif(ord($char) === 9){
                    $string = $this->interactive($string);
                    fwrite(STDOUT, "\r");
                    fwrite(STDOUT, $this->write_prefix($colored_cli).$string);
                }elseif(ord($char) === 27){

                }elseif(ord($char) === 91){

                }elseif(ord($char) === 65){
                    if(isset($this->last_commands[$this->last_commands_cursor])){
                        fwrite(STDOUT, str_repeat("\x08 \x08", strlen($string)));
                        $string = $this->last_commands[$this->last_commands_cursor];
                        fwrite(STDOUT, "\r");
                        fwrite(STDOUT,  $this->write_prefix($colored_cli).$string);
                        $this->last_commands_cursor++;
                        if($this->last_commands_cursor > count($this->last_commands) - 1){
                            $this->last_commands_cursor = count($this->last_commands) - 1;
                        }
                    }
                }elseif(ord($char) === 66){
                    if($this->last_commands_cursor >= 0)
                        $this->last_commands_cursor--;

                    if($this->last_commands_cursor < 0){
                        fwrite(STDOUT, str_repeat("\x08 \x08", strlen($string)));
                        $string = '';
                        $this->last_commands_cursor = 0;
                    }elseif(isset($this->last_commands[$this->last_commands_cursor])){
                        fwrite(STDOUT, str_repeat("\x08 \x08", strlen($string)));
                        $string = $this->last_commands[$this->last_commands_cursor];
                        fwrite(STDOUT, "\r");
                        fwrite(STDOUT,  $this->write_prefix($colored_cli).$string);
                    }
                }else{
                    $this->tab_count = 0;
                    $this->last_tab = '';
                    fwrite(STDOUT, $char);
                    $string .= $char;
                }

            }
        }
        shell_exec('stty ' . $_tty);
        return $string.PHP_EOL;
    }

    public function interactive($_input){
        $inputs = [
            "help",
            "run",
            "test",
            "info",
            "state",
            "cli",
        ];
        sort($inputs, SORT_STRING);
        $exploded = explode(" ",$_input);
        if(!$this->tab_count){
            $last = array_pop($exploded);
        }else{
            array_pop($exploded);
            $last = $this->last_tab;
        }

        $string_splitted = str_split($last);
        $closest = [];
        $wrong = [];
        for($i=0;$i<=count($string_splitted)-1;$i++){
            foreach($inputs as $input){
                if(!isset($closest[$input]))
                    $closest[$input] = 0;

                $input_splitted = str_split($input);
                if(isset($input_splitted[$i]) && $string_splitted[$i] === $input_splitted[$i]){
                    $closest[$input] += 2;
                }else{
                    if(in_array($string_splitted[$i], $input_splitted)){
                        $closest[$input]++;
                    }else{
                        $wrong[] = $input;
                    }
                }
            }
        }
        foreach($wrong as $wrong_key){
            unset($closest[$wrong_key]);
        }

        $closest = array_filter($closest);
        if(empty($closest)){
            $exploded[] = $last;
            return implode(" ",$exploded);
        }
        $max = max($closest);
        if($max){
            if($this->tab_count > count($closest)-1)
                $this->tab_count = 0;

            $helpie = $this->get_max($closest, $max);
            if(strlen($this->last_complite) > strlen($helpie)){
                fwrite(STDOUT, str_repeat("\x08 \x08", strlen($this->last_complite) - strlen($helpie)));
            }
            $this->last_complite = $helpie;
            $exploded[] = $this->last_complite;
        }else{
            $exploded[] = $last;
        }

        $this->last_tab = $last;
        $this->tab_count++;
        return implode(" ",$exploded);
    }

    protected function get_max($closest, $max, $depth = 0){
        $keys = array_keys($closest, $max);
        sort($keys, SORT_STRING);
        if(count($keys) > $this->tab_count - $depth){
            return $keys[$this->tab_count - $depth];
        }else{
            foreach($keys as $key){
                unset($closest[$key]);
            }
            if(!empty($closest))
                return $this->get_max($closest, max($closest), $depth+count($keys));
        }
        return array_shift($keys);
    }
}