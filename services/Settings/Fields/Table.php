<?php

namespace Netdust\Services\Settings\Fields;

use Netdust\Logger\Logger;

class Table
{

    public $tableClass = 'message-setting-table';

    public function input($field)
    {
        if($field->addon('header')) {
            $table = '<table class="'.esc_html($this->tableClass).'"><thead><tr>';
            foreach ($field->addon('header') as $key => $heading) {
                $table .= '<th class="fc_head_'.esc_attr($key).'">'.wp_kses_post($heading).'</th>';
            }
            $table .= '</tr></thead><tbody>';

            foreach ($field->addon('rows') as $row ) {
                $table .= '<tr>';
                foreach ($field->addon('header') as $key => $heading) {
                    $table .= '<td>'.$row[$key].'</td>';
                }
                $table .= '</tr>';
            }
            $table .= '</tbody></table>';

            echo $table;
        }

    }


}
