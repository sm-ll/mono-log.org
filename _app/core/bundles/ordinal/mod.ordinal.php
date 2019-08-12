<?php

class Modifier_ordinal extends Modifier
{
	public function index($value, $parameters=array())
	{
		$last        = (int) substr($value, -1);
        $second_last = (int) substr($value, -2, 1);
		$wrap_start  = (isset($parameters[0])) ? '<' . $parameters[0] . '>' : '';
		$wrap_end    = (isset($parameters[0])) ? '</' . $parameters[0] . '>' : '';
		
		switch ($last) {
			case 1:
				return $value . $wrap_start . 'st' . $wrap_end;
				break;
			
			case 2:
				return $value . $wrap_start . 'nd' . $wrap_end;
				break;
			
			case 3:
                // ohhh english, it's 13th not 13rd!
				return ($second_last == 1) ? $value . $wrap_start . 'th' . $wrap_end : $value . $wrap_start . 'rd' . $wrap_end;
				break;
			
			default:
				return $value . $wrap_start . 'th' . $wrap_end;
				break;
		}
	}
}