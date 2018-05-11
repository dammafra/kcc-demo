<?php 
    $backend_url = 'http://' . $_ENV['BACKEND_HOST'];
    $username = $_ENV['USERNAME'];
    $password = $_ENV['PASSWORD'];
    $frontend_hostname = gethostname();

    $api_message = file_get_contents($backend_url);

    $backend_hostname = file_get_contents($backend_url . '/hostname');

    $post_data = 'username=' . $username . '&password=' . $password; 
    $HTTP = array(
        'http' =>
            array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $post_data
        )
    );
    $context = stream_context_create($HTTP);
    $authentication_response = file_get_contents($backend_url.'/auth', false, $context);
    if($authentication_response === FALSE)
        $authentication_response = '<td style=\'color:red;\'>Authentication failed</td>';
    else
        $authentication_response = '<td style=\'color:green;\'>' . $authentication_response . '</td>';
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Kubernetes Core Concepts</title>

        <!-- Bootstrap: Latest compiled and minified CSS -->
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' integrity='sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u' crossorigin='anonymous'>
        <!-- Bootstrap:  Optional theme -->
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css' integrity='sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp' crossorigin='anonymous'>
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.12/css/all.css" integrity="sha384-G0fIWCsCzJIMAVNQPfjH08cyYaUtMwjJwqiRKxxE/rx96Uroj1BtIQ6MLJuheaO9" crossorigin="anonymous">

        <!-- Custom style -->
        <style>
            #logo {
                float: right;
                width: 150px;
                margin-top: 20px;            
            }

            td {
                padding: 5px 20px 5px 0px;
            }
            
            span {
                margin-right: 10px;
            }

            @media (max-width: 800px) {
                #logo {
                    display: none;         
                }  

                body {
                    padding: 20px;
                }
            }
        </style>
        
        <!-- jQuery -->
        <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js'></script>
        <!-- Bootstrap: Latest compiled and minified JavaScript -->
        <script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js' integrity='sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa' crossorigin='anonymous'></script>
    </head>

    <body>
        <div class='container'>
            <div class='row'>
                <div>
                    <img id='logo' class='img-responsive float-right' src='logo.png'>
                    <h1>Kubernetes Core Concepts in Action</h1>
                </div>
                <table>
                    <tr>
                        <td><span class="fas fa-desktop"></span>This page was served by: </td>
                        <td><?php echo $frontend_hostname ?></td>
                    </tr>

                    <tr>
                        <td><span class="fas fa-server"></span>The API server is: </td>
                        <td><?php echo $backend_hostname ?></td>
                    </tr>

                    <tr>
                        <td><span class="fas fa-comment"></span>The API server main endpoint says: </td>
                        <td><?php echo $api_message ?></td>
                    </tr>
                    
                    <tr>
                        <td><span class="fas fa-lock"></span>The API server auth endpoint says: </td>
                        <?php echo $authentication_response ?>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>


