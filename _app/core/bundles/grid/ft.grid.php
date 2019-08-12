<?php
/**
 * Fieldtype_grid
 * Grid fieldtype
 */
class Fieldtype_grid extends Fieldtype
{
    /**
     * Renders the grid field
     *
     * @return void
     */
    public function render()
    {
        // determine boundaries
        $max_rows = array_get($this->field_config, 'max_rows', false);
        $min_rows = array_get($this->field_config, 'min_rows', 0);

        $max_rows_attr = ($max_rows) ? " data-max-rows='$max_rows'" : '';
        $min_rows_attr = ($min_rows) ? " data-min-rows='$min_rows'" : '';
        $starting_rows = array_get($this->field_config, 'starting_rows');

        // create header row
        // -------------------------------------------------------------------------
        $html = "<thead><tr>";
        $html .= "<th class='row-count'></th>";

        // Set Width
        foreach ($this->field_config['fields'] as $key => $cell_field_config) {
            $width = array_get($cell_field_config, 'width', 'auto');
            $html .= "<th style='width:{$width}'>";
                $html .= array_get($cell_field_config, 'display', Slug::prettify($key));

                $instructions = array_get($cell_field_config, 'instructions');
                
                if ($instructions) {
                    $html .= "<small>" . htmlspecialchars($instructions) . "</small>";
                }

            $html .= "</th>";
        }

        $html .= "</tr></thead>";


        // create grid rows
        // -------------------------------------------------------------------------
        $html .= "<tbody>";

        # rows to render
        if ($starting_rows && $starting_rows > $min_rows) {
            $rows_to_render = $starting_rows;
        } elseif ($min_rows) {
            $rows_to_render = $min_rows;
        } else {
            $rows_to_render = 1;
        }

        # render the rows
        $i = 1;
        if (isset($this->field_data) && is_array($this->field_data) && count($this->field_data) > 0) {
            foreach ($this->field_data as $key => $row) {
                $html_row = "<tr>";
                $html_row .= "<th class='row-count drag-indicator'><div class='count'>{$i}</div>";
                $html_row .= "<a href='#' class='grid-delete-row confirm'><span class='ss-icon'>delete</span></a>";
                $html_row .= "</td></th>";

                foreach ($this->field_config['fields'] as $cell_field_name => $cell_field_config) {
                    $column      = key($row);
                    $column_data = isset($row[$cell_field_name]) ? $row[$cell_field_name] : '';

                    $default  = isset($cell_field_config['default']) ? $cell_field_config['default'] : '';
                    $celltype = array_get($cell_field_config, 'type', 'text');

                    $html_row .= "<td class='cell-{$celltype}' data-default='{$default}'>";

                    $name = $this->field . '][' . $key . '][' . $cell_field_name;

                    $html_row .= Fieldtype::render_fieldtype($celltype, $name, $cell_field_config, $column_data);
                    $html_row .= "</td>";
                }
                $html_row .= "</tr>\n";

                $html .= $html_row;

                $i++;
            }
        } else { # no rows, set a blank one
            for ($i; $i <= $rows_to_render; $i++) {
                $html .= $this->render_empty_row($i - 1);
            }
        }
        $html .= "</tbody>\n</table>\n";

        // If max_rows is 1, we shouldn't have an "add row" at all.
        if (array_get($this->field_config, 'max_rows', 9999) > $starting_rows) {
            $html .= "<a href='#' class='grid-add-row btn btn-small btn-icon'><span class='ss-icon'>add</span></a>";
        }

        $empty_row = ' data-empty-row="' . htmlspecialchars($this->render_empty_row(0)) . '"';
        $html      = "<table class='grid table-list' tabindex='{$this->tabindex}'" . $max_rows_attr . $min_rows_attr . $empty_row . ">" . $html;

        return $html;
    }

    public function render_empty_row($index)
    {
        $row_num = $index + 1;
        $row     = "<tr>";
        $row .= "<th class='row-count drag-indicator'><div class='count'>{$row_num}</div><a href='#' class='grid-delete-row confirm'><span class='ss-icon'>delete</span></a></td></th>";

        foreach ($this->field_config['fields'] as $cell_field_name => $cell_field_config) {

            $celltype = array_get($cell_field_config, 'type', 'text');
            $default  = array_get($cell_field_config, 'default', '');
            $name     = $this->field . '][' . $index . '][' . $cell_field_name;

            // This makes the config a little different from "real" fields
            // If a fieldtype is leveraging caching, one of these fields will throw it off.
            // Making this a bit more unique will prevent that.
            $cell_field_config['grid_placeholder_row'] = true;

            $row .= "<td class='cell-{$celltype}' data-default='{$default}'>" . Fieldtype::render_fieldtype($celltype, $name, $cell_field_config, $default, null, '[yaml]', 'rename_me') . "</td>";
        }
        $row .= "</tr>\n";

        return $row;
    }

    public function process()
    {
        foreach ($this->field_data as $row => $column) {
            foreach ($column as $field => $data) {
                $settings = $this->settings['fields'][$field];
                $the_field_type = array_get($settings, 'type', 'text');
                $this->field_data[$row][$field] = Fieldtype::process_field_data($the_field_type, $data, $settings);
            }
        }

        // if $this->field_data doesn't contain real values, make it an empty string for the people
        if (Helper::isEmptyArray($this->field_data)) {
            $this->field_data = "";
        }        

        return $this->field_data;
    }
}
