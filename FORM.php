<?php
class FORM
{
private $fields=array();
private $clean=array();
private $submited;
private $csrf='csrf';
private $hasErrors=false;


public function __construct($csrf=null)
    {
        if($csrf) $this->csrf=md5('trolo'.$csrf);
    }

public function addLabel($name,$caption=null,$value=null)
    {
        $this->fields[$name]=array(
            'type'=>'label',
            'caption'=>isset($caption) ? $caption : $name,
            'value'=>$value
        );
        return true;
    }

public function addText($name,$caption=null,$value=null,$regex='~^.*$~',$error_message="Ошибка! Не правильный формат!")
    {
        $this->fields[$name]=array(
            'type'=>'text',
            'caption'=>isset($caption) ? $caption : $name,
            'value'=>$value,
            'regex'=>$regex,
            'error_message'=>$error_message

        );
        return true;
    }

public function addPassword($name,$caption=null,$value=null,$regex='~^.*$~',$error_message="Ошибка! Не правильный формат!")
    {
        $this->fields[$name]=array(
            'type'=>'password',
            'caption'=>isset($caption) ? $caption : $name,
            'value'=>$value,
            'regex'=>$regex,
            'error_message'=>$error_message
        );
        return true;
    }

public function addTextArea($name,$caption=null,$value=null,$escape=true,$rows=5,$cols=60)
    {
        $this->fields[$name]=array(
            'type'=>'textarea',
            'caption'=>isset($caption) ? $caption : $name,
            'value'=>$value,
            'escape'=>$escape ? true : false,
            'rows'=>intval($rows),
            'cols'=>intval($cols)
        );
        return true;
    }

public function addCheckBox($name,$caption=null,$value=null,$check=false)
    {
        $this->fields[$name]=array(
            'type'=>'checkbox',
            'caption'=>isset($caption) ? $caption : $name,
            'value'=>$value ? $value : '1',
            'check'=>$check ? true : false
        );
        return true;
    }

public function addDropdown($name,$caption=null,$values=null,$selected=null)
    {
        $this->fields[$name]=array(
            'type'=>'dropdown',
            'caption'=>isset($caption) ? $caption : $name,
            'value'=>is_array($values) ? $values : array($values=>$values),
            'selected'=>$selected
        );
        return true;
    }


public function addHidden($name,$value=1)
    {
        $this->fields[$name]=array(
            'type'=>'hidden',
            'value'=>$value,
        );
        return true;
    }


public function addDescription($field_name,$description)
    {
        if(isset($this->fields[$field_name]))
            {
                $this->fields[$field_name]['description']=$description;
            }
        else
            {
                throw new Exception('This form field does not exists!');
            }
    }

private function filter($text)
    {
        /*todo
         * add some filtering functions here!
         */
        return $text;
    }

private function generateCSRF()
    {
        return md5('dazdraperma'.$_SERVER['REQUEST_URI'].$_SERVER['HTTP_HOST'].$_SERVER['HTTP_USER_AGENT']);
    }

private function validateCSRF($a=null)
    {
        if($a)
            return ($a==md5('dazdraperma'.$_SERVER['REQUEST_URI'].$_SERVER['HTTP_HOST'].$_SERVER['HTTP_USER_AGENT']));
        else
            return false;
    }

public function submit()
    {
        if(isset($_POST[$this->csrf]) and $this->validateCSRF($_POST[$this->csrf]))
            {
            foreach(array_keys($this->fields) as $field)
                {
                    if(isset($_POST[md5($this->csrf.$field)]))
                        {
                            if($this->fields[$field]['type']=='text' or $this->fields[$field]['type']=='password')
                                {
                                    if(empty($_POST[md5($this->csrf.$field)]) or preg_match($this->fields[$field]['regex'],htmlspecialchars($_POST[md5($this->csrf.$field)],ENT_QUOTES,'UTF-8')))
                                        {
                                            $this->fields[$field]['value']=$_POST[md5($this->csrf.$field)];
                                            $this->clean[$field]=htmlspecialchars($_POST[md5($this->csrf.$field)],ENT_QUOTES,'UTF-8');
                                        }
                                    else
                                        {
                                            $this->fields[$field]['value']=$_POST[md5($this->csrf.$field)];
                                            $this->setError($field,$this->fields[$field]['error_message']);
                                            $this->hasErrors=true;
                                            unset($this->clean[$field]);
                                        }
                                }
                            elseif($this->fields[$field]['type']=='textarea')
                                {
                                    $this->fields[$field]['value']=$_POST[md5($this->csrf.$field)];
                                    $this->clean[$field]=$this->filter($_POST[md5($this->csrf.$field)]);
                                }
                            elseif($this->fields[$field]['type']=='checkbox')
                                {
                                    if(isset($_POST[md5($this->csrf.$field)]))
                                        {
                                            $this->fields[$field]['check']=true;
                                            $this->clean[$field]=$this->filter($_POST[md5($this->csrf.$field)]);
                                        }
                                }
                            elseif($this->fields[$field]['type']=='dropdown')
                                {
                                    if(isset($_POST[md5($this->csrf.$field)]) and in_array($_POST[md5($this->csrf.$field)],array_keys($this->fields[$field]['value'])))
                                        {
                                            $this->fields[$field]['selected']=$_POST[md5($this->csrf.$field)];
                                            $this->clean[$field]=$this->filter($_POST[md5($this->csrf.$field)]);
                                        }
                                }
                            elseif($this->fields[$field]['type']=='hidden')
                                {

                                    if(isset($_POST[md5($this->csrf.$field)])){
                                        $this->clean[$field]=$this->filter($_POST[md5($this->csrf.$field)]);
                                    }
                                }
                            else
                                {
                                    throw new Exception('Strange value in form...');
                                }
                        }
                }
            $this->submited=true;
            return true;
            }
        else
            return false;
    }

public function getElementName($elementName)
    {
        return md5($this->csrf.$elementName);
    }

public function render($submit_text='Сохранить',$reset_text='Отмена')
    {
        ob_start();//todo - jquery - on edit! + random forms which are hidded by jquery
        ?>
    <form action="<?php echo $_SERVER['REQUEST_URI'];?>" method="post">
        <input name="<?php echo md5($this->csrf);?>" type="hidden" value="<?php echo md5(time().'lolz');?>">
        <input name="<?php echo $this->csrf;?>" type="hidden" value="<?php echo $this->generateCSRF();?>">
        <input name="<?php echo md5(time());?>" type="hidden" value="<?php echo md5(session_id().'lolz');?>">
        <input name="<?php echo md5('a'.$this->csrf);?>" type="hidden" value="<?php echo md5(session_id().'2g2');?>">
        <input name="<?php echo md5('fuckoff'.$this->csrf);?>" type="hidden" value="<?php echo md5('a4sd'.$this->generateCSRF());?>">

        <table border="0" cellpadding="3" cellspacing="0" align="center" width="100%">
            <tr>
                <td width="33%"></td>
                <td width="33%"></td>
                <td width="33%"></td>
            </tr>
            <?php
            foreach(array_keys($this->fields) as $field)
                {
                    if($this->fields[$field]['type']=='text' or $this->fields[$field]['type']=='password')
                        {
                            ?>
                            <?php if(isset($this->fields[$field]['error'])):?>
                            <tr class="form_error" title="<?php echo $this->fields[$field]['error'];?>">
                                <td colspan="3" align="center"><?php echo $this->fields[$field]['error'];?></td>
                            </tr>
    <tr class="form_error" title="<?php echo $this->fields[$field]['error'];?>">
<?php else: ?>
    <tr>
<?php endif;?>
                            <td align="right"><?php echo $this->fields[$field]['caption'];?></td>
                            <td align="left" colspan="<?php if(isset($this->fields[$field]['description'])) echo 1; else echo 2;?>">
                                <input name="<?php echo md5($this->csrf.$field);?>"
                                       type="<?php echo $this->fields[$field]['type'];?>"
                                       value="<?php echo $this->fields[$field]['value'];?>">
                            </td>
                            <?php if(isset($this->fields[$field]['description'])) echo '<td align="left">'.$this->fields[$field]['description'].'</td>';?>
    </tr>
<?php
                        }
                    elseif($this->fields[$field]['type']=='textarea')
                        {
                            ?>
                            <?php if(isset($this->fields[$field]['error'])):?>
                            <tr class="form_error" title="<?php echo $this->fields[$field]['error'];?>">
                                <td colspan="3" align="center"><?php echo $this->fields[$field]['error'];?></td>
                            </tr>
    <tr class="form_error" title="<?php echo $this->fields[$field]['error'];?>">
<?php else: ?>
    <tr>
<?php endif;?>
                            <td  colspan="<?php if(isset($this->fields[$field]['description'])) echo 2; else echo 3;?>">
                                    <?php echo $this->fields[$field]['caption'];?><br>
                                <textarea class="-metrika-nokeys" rows="<?php echo $this->fields[$field]['rows'];?>" cols="<?php echo $this->fields[$field]['cols'];?>" style="width: 100%;" name="<?php echo md5($this->csrf.$field);?>"><?php echo $this->fields[$field]['value'];?></textarea>
                            </td>
                            <?php if(isset($this->fields[$field]['description'])) echo '<td align="left">'.$this->fields[$field]['description'].'</td>'; else echo '<td></td>';?>
    </tr>
<?php
                        }
                    elseif($this->fields[$field]['type']=='checkbox')
                        {
                            ?>
                            <?php if(isset($this->fields[$field]['error'])):?>
                            <tr class="form_error" title="<?php echo $this->fields[$field]['error'];?>">
                                <td colspan="3" align="center"><?php echo $this->fields[$field]['error'];?></td>
                            </tr>
    <tr class="form_error" title="<?php echo $this->fields[$field]['error'];?>">
<?php else: ?>
    <tr>
<?php endif;?>
                            <td colspan="<?php if(isset($this->fields[$field]['description'])) echo 2; else echo 3;?>" align="center">
                                <input type="checkbox" name="<?php echo md5($this->csrf.$field);?>" value="<?php echo $this->fields[$field]['value'];?>" <?php if($this->fields[$field]['check']) echo ' checked="checked" ';?>><?php echo $this->fields[$field]['caption'];?>
                            </td>
                            <?php if(isset($this->fields[$field]['description'])) echo '<td align="left">'.$this->fields[$field]['description'].'</td>'; else echo '<td></td>';?>
    </tr>
<?php
                        }
                    elseif($this->fields[$field]['type']=='dropdown')
                        {
                            ?>
                            <?php if(isset($this->fields[$field]['error'])):?>
                            <tr class="form_error" title="<?php echo $this->fields[$field]['error'];?>">
                                <td colspan="3" align="center"><?php echo $this->fields[$field]['error'];?></td>
                            </tr>
    <tr class="form_error" title="<?php echo $this->fields[$field]['error'];?>">
<?php else: ?>
    <tr>
<?php endif;?>
                            <td align="right"><?php echo $this->fields[$field]['caption'];?></td>
                            <td align="left"  colspan="<?php if(isset($this->fields[$field]['description'])) echo 1; else echo 2;?>">
                                <select name="<?php echo md5($this->csrf.$field);?>">
                                    <?php foreach(array_keys($this->fields[$field]['value']) as $value):?>
                                    <option value="<?php echo $value;?>"
                                        <?php if($value==$this->fields[$field]['selected']) echo ' selected="selected" ';?>
                                        >
                                        <?php echo $this->fields[$field]['value'][$value];//. ' '.$value.'='.$this->fields[$field]['selected'] ;?>
                                    </option>
                                    <?php endforeach;?>
                                </select>
                            </td>
                            <?php if(isset($this->fields[$field]['description'])) echo '<td align="left">'.$this->fields[$field]['description'].'</td>'; else echo '<td></td>';?>
</tr>
<?php
                        }
                    elseif($this->fields[$field]['type']=='label')
                        {
?><tr>
     <td align="center" colspan="3"><?php echo $this->fields[$field]['caption'];?></td>
</tr><?php
                        }
                    elseif($this->fields[$field]['type']=='hidden')
                        {
?><input name="<?php echo md5($this->csrf.$field);?>" type="hidden" value="<?php echo $this->fields[$field]['value'];?>"><?php
                        }

                }
            ?>
            <tr>
                <td></td>
                <td align="center">
                <p>
                    <?php if($submit_text) echo '<input type="submit" value="'.$submit_text.'">';?>
                    <?php if($reset_text) echo '<input type="reset" value="'.$reset_text.'">';?>
                </p>
                </td>
                <td></td>
            </tr>
        </table>
    </form>
    <?php
        return ob_get_clean();
    }


public function setError($name,$error_text)
    {
        if(isset($this->fields[$name]))
            {
                $this->fields[$name]['error']=$error_text;
                return true;
            }
        else
            {
                return false;
            }
    }

public function hasError()
    {
        return $this->hasErrors;
    }

public function __toString()
    {
        return $this->render();
    }

public function getClean($name=null)
    {
        if($this->submited)
            if($name)
                {
                return isset($this->clean[$name]) ? $this->clean[$name] : false;
                }
            else
                {
                return $this->clean;
                }

        else
            return false;
    }
}

