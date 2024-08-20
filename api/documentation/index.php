<?php
require_once('../../backend/functions.php');
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("documentation/index.php","",$_SERVER['SCRIPT_NAME']);
?>

<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <title>API - Documentation</title>
    <meta http-equiv="cleartype" content="on">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/hightlightjs-dark.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.8.0/highlight.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;1,300&family=Source+Code+Pro:wght@300&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="css/style.css?v=3" media="all">
    <script>
        hljs.initHighlightingOnLoad();
    </script>
    <style>
        .post_label {
            background: #a95f0d;
            color: white;
            padding: 4px;
            border-radius: 4px;
            pointer-events: none;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .get_label {
            background: #12a90d;
            color: white;
            padding: 4px;
            border-radius: 4px;
            pointer-events: none;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .put_label {
            background: #0d44a9;
            color: white;
            padding: 4px;
            border-radius: 4px;
            pointer-events: none;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .ok_response {
            color: darkgreen;
        }
        .border0 {
            border-bottom: 0 !important;
        }
        .border1 {
            border-bottom: 1px solid #ccc;
        }
        .warning_response {
            color: darkorange;
        }
    </style>
</head>

<body class="one-content-column-version">
<div class="left-menu">
    <div class="content-logo">
        <div class="logo">
            <img src="images/logo.png" height="32" />
            <span>API Documentation</span>
        </div>
        <button class="burger-menu-icon" id="button-menu-mobile">
            <svg width="34" height="34" viewBox="0 0 100 100"><path class="line line1" d="M 20,29.000046 H 80.000231 C 80.000231,29.000046 94.498839,28.817352 94.532987,66.711331 94.543142,77.980673 90.966081,81.670246 85.259173,81.668997 79.552261,81.667751 75.000211,74.999942 75.000211,74.999942 L 25.000021,25.000058"></path><path class="line line2" d="M 20,50 H 80"></path><path class="line line3" d="M 20,70.999954 H 80.000231 C 80.000231,70.999954 94.498839,71.182648 94.532987,33.288669 94.543142,22.019327 90.966081,18.329754 85.259173,18.331003 79.552261,18.332249 75.000211,25.000058 75.000211,25.000058 L 25.000021,74.999942"></path></svg>
        </button>
    </div>
    <div class="mobile-menu-closer"></div>
    <div class="content-menu">
        <div class="content-infos">
            <div class="info"><b>Version:</b> 1.1</div>
        </div>
        <ul>
            <li class="scroll-to-link" data-target="content-login">
                <a>LOGIN</a>
            </li>
            <li class="scroll-to-link" data-target="content-register">
                <a>REGISTER</a>
            </li>
            <li class="scroll-to-link" data-target="content-get-users">
                <a>GET USERS</a>
            </li>
            <li class="scroll-to-link" data-target="content-get-user">
                <a>GET USER</a>
            </li>
            <li class="scroll-to-link" data-target="content-add-user">
                <a>ADD USER</a>
            </li>
            <li class="scroll-to-link" data-target="content-edit-user">
                <a>EDIT USER</a>
            </li>
            <li class="scroll-to-link" data-target="content-get-plans">
                <a>GET PLANS</a>
            </li>
            <li class="scroll-to-link" data-target="content-get-tours">
                <a>GET TOURS</a>
            </li>
            <li class="scroll-to-link" data-target="content-get-tour-statistics">
                <a>GET TOUR STATISTICS</a>
            </li>
        </ul>
    </div>
</div>

<div class="content-page">
    <div class="content">
        <div class="overflow-hidden content-section" id="content-login">
            <h2>LOGIN</h2>
            <p>Login to the application and return the auth token for other API calls.</p>
            <p>
                <b class="post_label">POST</b> <code class="higlighted break-word"><?php echo $base_url; ?>login</code>
            </p>
            <br>
            <h4>AUTHORIZATION</h4>
            <p>All</p>
            <br>
            <h4>QUERY PARAMETERS</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Mandatory</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code class="higlighted">username</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The user's username</td>
                </tr>
                <tr>
                    <td><code class="higlighted">password</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The user's password</td>
                </tr>
                </tbody>
            </table>
            <br>
            <h4>RESPONSE</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr class="ok_response">
                    <th class="border0" colspan="2">
                        CODE 200
                    </th>
                    <th class="border0">Logged in successfully</th>
                </tr>
                <tr>
                    <td><code class="higlighted">token</code></td>
                    <td>String</td>
                    <td>The authentication token to use in API calls</td>
                </tr>
                <tr class="border1">
                    <td><code class="higlighted">login_url</code></td>
                    <td>String</td>
                    <td>The link to login directly without entering credentials</td>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 201
                    </th>
                    <th>User is blocked</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 202
                    </th>
                    <th>Incorrect password</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 203
                    </th>
                    <th>Incorrect username</th>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="overflow-hidden content-section" id="content-register">
            <h2>REGISTER</h2>
            <p>Allows to register a user from an external site.</p>
            <p>
                <b class="post_label">POST</b> <code class="higlighted break-word"><?php echo $base_url; ?>register</code>
            </p>
            <br>
            <h4>AUTHORIZATION</h4>
            <p>All - SaaS version</p>
            <br>
            <h4>QUERY PARAMETERS</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Mandatory</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code class="higlighted">username</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The user's username</td>
                </tr>
                <tr>
                    <td><code class="higlighted">email</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The user's e-mail address</td>
                </tr>
                <tr>
                    <td><code class="higlighted">password</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The user's password</td>
                </tr>
                <tr>
                    <td><code class="higlighted">id_plan</code></td>
                    <td></td>
                    <td>Integer</td>
                    <td>The id of the plan to associate to the user</td>
                </tr>
                </tbody>
            </table>
            <br>
            <h4>RESPONSE</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr class="ok_response">
                    <th class="border0" colspan="2">
                        CODE 200
                    </th>
                    <th class="border0">User registered successfully</th>
                </tr>
                <tr>
                    <td><code class="higlighted">id_user</code></td>
                    <td>Integer</td>
                    <td>The id of the added user</td>
                </tr>
                <tr class="border1">
                    <td><code class="higlighted">validate_mail</code></td>
                    <td>Boolean</td>
                    <td>If the user need to validate it's account</td>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 201
                    </th>
                    <th>Username already registered</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 202
                    </th>
                    <th>E-Mail already registered</th>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="overflow-hidden content-section" id="content-get-users">
            <h2>GET USERS</h2>
            <p>Retrieve the list of all the users.</p>
            <p>
                <b class="get_label">GET</b> <code class="higlighted break-word"><?php echo $base_url; ?>users</code>
            </p>
            <br>
            <h4>AUTHORIZATION</h4>
            <p>Administrator - SaaS version</p>
            <br>
            <h4>QUERY PARAMETERS</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Mandatory</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code class="higlighted">token</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The authentication token generated from login API</td>
                </tr>
                <tr>
                    <td><code class="higlighted">offset</code></td>
                    <td></td>
                    <td>Integer</td>
                    <td>The offset of the first item returned</td>
                </tr>
                <tr>
                    <td><code class="higlighted">limit</code></td>
                    <td></td>
                    <td>Integer</td>
                    <td>The maximum number of entries to return</td>
                </tr>
                </tbody>
            </table>
            <br>
            <h4>RESPONSE</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr class="ok_response">
                    <th class="border0" colspan="2">
                        CODE 200
                    </th>
                    <th class="border0">Ok</th>
                </tr>
                <tr class="border1">
                    <td><code class="higlighted">data</code></td>
                    <td>Array</td>
                    <td>Contains the list of all the users</td>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 404
                    </th>
                    <th>Users not found</th>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="overflow-hidden content-section" id="content-get-user">
            <h2>GET USER</h2>
            <p>Retrieve a user's details.</p>
            <p>
                <b class="get_label">GET</b> <code class="higlighted break-word"><?php echo $base_url; ?>user</code>
            </p>
            <br>
            <h4>AUTHORIZATION</h4>
            <p>Administrator - SaaS version</p>
            <br>
            <h4>QUERY PARAMETERS</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Mandatory</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code class="higlighted">token</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The authentication token generated from login API</td>
                </tr>
                <tr>
                    <td><code class="higlighted">id</code></td>
                    <td>&#x2713;</td>
                    <td>Integer</td>
                    <td>The id of the user</td>
                </tr>
                </tbody>
            </table>
            <br>
            <h4>RESPONSE</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr class="ok_response">
                    <th class="border0" colspan="2">
                        CODE 200
                    </th>
                    <th class="border0">Ok</th>
                </tr>
                <tr class="border1">
                    <td><code class="higlighted">data</code></td>
                    <td>Array</td>
                    <td>Contains the user information</td>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 404
                    </th>
                    <th>User not found</th>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="overflow-hidden content-section" id="content-add-user">
            <h2>ADD USER</h2>
            <p>Adding a user by the administrator.</p>
            <p>
                <b class="post_label">POST</b> <code class="higlighted break-word"><?php echo $base_url; ?>user</code>
            </p>
            <br>
            <h4>AUTHORIZATION</h4>
            <p>Administrator - SaaS version</p>
            <br>
            <h4>QUERY PARAMETERS</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Mandatory</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code class="higlighted">token</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The authentication token generated from login API</td>
                </tr>
                <tr>
                    <td><code class="higlighted">username</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The user's username</td>
                </tr>
                <tr>
                    <td><code class="higlighted">email</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The user's e-mail address</td>
                </tr>
                <tr>
                    <td><code class="higlighted">password</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The user's password</td>
                </tr>
                <tr>
                    <td><code class="higlighted">role</code></td>
                    <td>&#x2713;</td>
                    <td>Enum</td>
                    <td>The user's role chosen from <code class="higlighted">super_admin</code>, <code class="higlighted">administrator</code>, <code class="higlighted">editor</code>, <code class="higlighted">customer</code></td>
                </tr>
                <tr>
                    <td><code class="higlighted">id_plan</code></td>
                    <td></td>
                    <td>Integer</td>
                    <td>The id of the plan to associate to the user</td>
                </tr>
                <tr>
                    <td><code class="higlighted">first_name</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's first name</td>
                </tr>
                <tr>
                    <td><code class="higlighted">last_name</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's last name</td>
                </tr>
                <tr>
                    <td><code class="higlighted">company</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's company</td>
                </tr>
                <tr>
                    <td><code class="higlighted">tax_id</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's tax id</td>
                </tr>
                <tr>
                    <td><code class="higlighted">street</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's address</td>
                </tr>
                <tr>
                    <td><code class="higlighted">city</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's city</td>
                </tr>
                <tr>
                    <td><code class="higlighted">postal_code</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's postal code</td>
                </tr>
                <tr>
                    <td><code class="higlighted">province</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's province</td>
                </tr>
                <tr>
                    <td><code class="higlighted">country</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's country</td>
                </tr>
                <tr>
                    <td><code class="higlighted">tel</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's telephone number</td>
                </tr>
                </tbody>
            </table>
            <br>
            <h4>RESPONSE</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr class="ok_response">
                    <th class="border0" colspan="2">
                        CODE 200
                    </th>
                    <th class="border0">User added successfully</th>
                </tr>
                <tr class="border1">
                    <td><code class="higlighted">id_user</code></td>
                    <td>Integer</td>
                    <td>The id of the added user</td>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 201
                    </th>
                    <th>Username already registered</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 202
                    </th>
                    <th>E-Mail already registered</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 406
                    </th>
                    <th>Authorization issue</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 407
                    </th>
                    <th>Invalid role value</th>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="overflow-hidden content-section" id="content-edit-user">
            <h2>EDIT USER</h2>
            <p>Editing a user by the administrator.</p>
            <p>
                <b class="put_label">PUT</b> <code class="higlighted break-word"><?php echo $base_url; ?>user</code>
            </p>
            <br>
            <h4>AUTHORIZATION</h4>
            <p>Administrator - SaaS version</p>
            <br>
            <h4>QUERY PARAMETERS</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Mandatory</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code class="higlighted">token</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The authentication token generated from login API</td>
                </tr>
                <tr>
                    <td><code class="higlighted">id_user</code></td>
                    <td>&#x2713;</td>
                    <td>Integer</td>
                    <td>The id of the user</td>
                </tr>
                <tr>
                    <td><code class="higlighted">username</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's username</td>
                </tr>
                <tr>
                    <td><code class="higlighted">email</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's e-mail address</td>
                </tr>
                <tr>
                    <td><code class="higlighted">password</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's password</td>
                </tr>
                <tr>
                    <td><code class="higlighted">role</code></td>
                    <td></td>
                    <td>Enum</td>
                    <td>The user's role chosen from <code class="higlighted">super_admin</code>, <code class="higlighted">administrator</code>, <code class="higlighted">editor</code>, <code class="higlighted">customer</code></td>
                </tr>
                <tr>
                    <td><code class="higlighted">id_plan</code></td>
                    <td></td>
                    <td>Integer</td>
                    <td>The id of the plan to associate to the user</td>
                </tr>
                <tr>
                    <td><code class="higlighted">active</code></td>
                    <td></td>
                    <td>Boolean</td>
                    <td>The status of the user account</td>
                </tr>
                <tr>
                    <td><code class="higlighted">first_name</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's first name</td>
                </tr>
                <tr>
                    <td><code class="higlighted">last_name</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's last name</td>
                </tr>
                <tr>
                    <td><code class="higlighted">company</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's company</td>
                </tr>
                <tr>
                    <td><code class="higlighted">tax_id</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's tax id</td>
                </tr>
                <tr>
                    <td><code class="higlighted">street</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's address</td>
                </tr>
                <tr>
                    <td><code class="higlighted">city</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's city</td>
                </tr>
                <tr>
                    <td><code class="higlighted">postal_code</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's postal code</td>
                </tr>
                <tr>
                    <td><code class="higlighted">province</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's province</td>
                </tr>
                <tr>
                    <td><code class="higlighted">country</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's country</td>
                </tr>
                <tr>
                    <td><code class="higlighted">tel</code></td>
                    <td></td>
                    <td>String</td>
                    <td>The user's telephone number</td>
                </tr>
                </tbody>
            </table>
            <br>
            <h4>RESPONSE</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr class="ok_response">
                    <th colspan="2">
                        CODE 200
                    </th>
                    <th>User updated successfully</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 201
                    </th>
                    <th>Username already registered</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 202
                    </th>
                    <th>E-Mail already registered</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 404
                    </th>
                    <th>User id not found</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 406
                    </th>
                    <th>Authorization issue</th>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 407
                    </th>
                    <th>Invalid role value</th>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="overflow-hidden content-section" id="content-get-plans">
            <h2>GET PLANS</h2>
            <p>Get all the available subscription plans.</p>
            <p>
                <b class="get_label">GET</b> <code class="higlighted break-word"><?php echo $base_url; ?>plans</code>
            </p>
            <br>
            <h4>AUTHORIZATION</h4>
            <p>All - SaaS version</p>
            <br>
            <h4>QUERY PARAMETERS</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Mandatory</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td colspan="4">No parameters.</td>
                </tr>
                </tbody>
            </table>
            <br>
            <h4>RESPONSE</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr class="ok_response">
                    <th class="border0" colspan="2">
                        CODE 200
                    </th>
                    <th class="border0">Ok</th>
                </tr>
                <tr class="border1">
                    <td><code class="higlighted">data</code></td>
                    <td>Array</td>
                    <td>Contains the list of all the plans</td>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 404
                    </th>
                    <th>No plans found</th>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="overflow-hidden content-section" id="content-get-tours">
            <h2>GET TOURS</h2>
            <p>Retrieve the list of all the tours of the authenticated user.</p>
            <p>
                <b class="get_label">GET</b> <code class="higlighted break-word"><?php echo $base_url; ?>tours</code>
            </p>
            <br>
            <h4>AUTHORIZATION</h4>
            <p>All</p>
            <br>
            <h4>QUERY PARAMETERS</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Mandatory</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code class="higlighted">token</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The authentication token generated from login API</td>
                </tr>
                <tr>
                    <td><code class="higlighted">offset</code></td>
                    <td></td>
                    <td>Integer</td>
                    <td>The offset of the first item returned</td>
                </tr>
                <tr>
                    <td><code class="higlighted">limit</code></td>
                    <td></td>
                    <td>Integer</td>
                    <td>The maximum number of entries to return</td>
                </tr>
                </tbody>
            </table>
            <br>
            <h4>RESPONSE</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr class="ok_response">
                    <th class="border0" colspan="2">
                        CODE 200
                    </th>
                    <th class="border0">Ok</th>
                </tr>
                <tr class="border1">
                    <td><code class="higlighted">data</code></td>
                    <td>Array</td>
                    <td>Contains the list of tours</td>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 404
                    </th>
                    <th>Tours not found</th>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="overflow-hidden content-section" id="content-get-tour-statistics">
            <h2>GET TOUR STATISTICS</h2>
            <p>Retrieve the statistics of a desired tour of the authenticated user.</p>
            <p>
                <b class="get_label">GET</b> <code class="higlighted break-word"><?php echo $base_url; ?>tour_stats</code>
            </p>
            <br>
            <h4>AUTHORIZATION</h4>
            <p>All</p>
            <br>
            <h4>QUERY PARAMETERS</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Mandatory</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><code class="higlighted">token</code></td>
                    <td>&#x2713;</td>
                    <td>String</td>
                    <td>The authentication token generated from login API</td>
                </tr>
                <tr>
                    <td><code class="higlighted">id_tour</code></td>
                    <td></td>
                    <td>Integer</td>
                    <td>The id of the tour (default all the tours)</td>
                </tr>
                <tr>
                    <td><code class="higlighted">type</code></td>
                    <td></td>
                    <td>Enum</td>
                    <td>The type of statistics chosen from <code class="higlighted">all</code>, <code class="higlighted">unique</code> (default all)</td>
                </tr>
                </tbody>
            </table>
            <br>
            <h4>RESPONSE</h4>
            <table>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr class="ok_response">
                    <th class="border0" colspan="2">
                        CODE 200
                    </th>
                    <th class="border0">Ok</th>
                </tr>
                <tr class="border1">
                    <td><code class="higlighted">data</code></td>
                    <td>Array</td>
                    <td>Contains the statistics of the tour</td>
                </tr>
                <tr class="warning_response">
                    <th colspan="2">
                        CODE 404
                    </th>
                    <th>Tour not found</th>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="js/script.js"></script>
</body>
</html>