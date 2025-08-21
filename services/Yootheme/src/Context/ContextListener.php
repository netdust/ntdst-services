<?php

namespace Netdust\eap_csf\Services\Yootheme\src\Context;

class ContextListener
{

    public function onBuilderType($data) {

        if ( 
            isset($data['fieldset']) && 
            isset($data['fieldset']['default']) && 
            isset($data['fieldset']['default']['fields'][2])
        ) {
            if ($data['fieldset']['default']['fields'][2]['title'] === 'Advanced') {
                $data['fieldset']['default']['fields'][2]['fields'][] = 'element_context_yt';
                $data['fields']['element_context_yt'] = [
                    "label" => "Element Context",
                    "type" => "button-panel",
                    "text" => "Settings",
                    "panel" => "element_context_yt",
                ];
            }
        }
        
        return $data;
    }
}
