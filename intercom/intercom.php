<?php

if (!defined('WHMCS'))
    die('This file cannot be accessed directly');

function intercom_config()
{
    $configarray = array(
        'name'          => 'Intercom',
        'description'   => 'Integrates Intercom into the WHMCS Client Area and sends client information. (Original Project by Goodbytes)',
        'version'       => '1.0',
        'author'        => 'Jake Walker',
        'fields'        => array(
            'app_id' => array(
                'FriendlyName'  => 'App ID',
                'Type'          => 'text',
                'Size'          => '64',
                'Description'   => 'Add your Intercom App ID',
                'Default'       => ''
            )
        )
    );

    return $configarray;
}

function intercom_activate()
{
    return array(
        'status'        => 'success',
        'description'   => 'Intercom module has been activated. Add your App ID, you can find this by logging into Intercom.'
    );
}

function intercom_deactivate()
{
    return array(
        'status'        => 'success',
        'description'   => 'Intercom module has been deactivated.'
    );
}

function intercom_upgrade($vars)
{
    // No upgrade path yet.
}

function intercom_output($vars)
{
    echo '
        <p>To configure, go to Setup -> Addon Modules -> Intercom -> Configure.</p>

        <p>More options coming soon. For assistance go to <a href="https://github.com/goodbytes-gb/Intercom-WHMCS-Module">https://github.com/goodbytes-gb/Intercom-WHMCS-Module</a></p>
    ';
}

function intercom_sidebar($vars)
{
    // n/a
}
