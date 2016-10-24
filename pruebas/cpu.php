<?php

// Server Hardware Information v1.0.0

?>

<html>
<head>
<title><?php echo $SERVER_NAME; ?> - Server Information</title>
<STYLE type=text/css>
BODY { FONT-SIZE: 8pt; COLOR: black; FONT-FAMILY: Verdana,arial, helvetica, serif; margin : 0 0 0 0;}
.style1 {
    color: #999999;
    font-weight: bold;
}
</STYLE>
</head>
<body>
<blockquote>
  <pre><p></p>
<span class="style1">Uptime:</span> 
<?php system("uptime"); ?>

<span class="style1">System Information:</span>
<?php system("uname -a"); ?>


<span class="style1">Memory Usage (MB):</span> 
<?php system("free -m"); ?>


<span class="style1">Disk Usage:</span> 
<?php system("df -h"); ?>


<span class="style1">CPU Information:</span> 
<?php system("cat /proc/cpuinfo | grep \"model name\\|processor\""); ?>
</pre>
  <p><br>
    <br>
    
</p>
</blockquote>

<?php

function get_server_memory_usage(){
 
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $memory_usage = $mem[2]/$mem[1]*100;
 
        return $memory_usage;
}

function get_server_cpu_usage(){

        $output = shell_exec('cat /proc/loadavg');
        $loadavg = substr($output,0,strpos($output," ")); 

        return $loadavg;
}

?>

<p><span class="description">Server Memory Usage:</span> <span class="result"><?= get_server_memory_usage() ?>%</span></p>
<p><span class="description">Server CPU Usage: </span> <span class="result"><?= get_server_cpu_usage() ?>%</span></p>
</body>
</html>