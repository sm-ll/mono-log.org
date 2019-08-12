<?php
class Modifier_table extends Modifier
{

	public function index($value, $parameters=array())
	{
		$rows = $value;
		$parse_markdown = $this->fetchConfig('parse_markdown', false, null, true);

		$html = '<table>';

		foreach ($rows as $row) {
			$html .= '<tr>';
			foreach ($row['cells'] as $cell) {
				$html .= '<td>';
				$html .= ($parse_markdown) ? Parse::markdown($cell) : $cell;
				$html .= '</td>';
			}
			$html .= '</tr>';
		}

		$html .= '</table>';

		return $html;
	}

}