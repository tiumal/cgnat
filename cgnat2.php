#!/usr/bin/env php
<?php
/**
 * PHP CGNAT MIKROTIK
 *
 * @author     Diorges Rocha <diorges@gis.net.br>
 * @copyright  (C) 2019 Diorges Rocha
 *
 */
chdir(dirname($argv[0]));
$options = getopt("c:s:e:t:o:imnh");
function _print_help()
{
    $help = <<<EOF
USO:
\$nomedoscript [-csetoh] 
OPTIONS:
-c                                           IP inicial do bloco CGNAT. ex.: 100.64.100.0
-s                                           IP inicial dos ips publicos utilizados para o CGNAT.
-e                                           IP final dos ips publicos utilizados para o CGNAT.
-t                                           Quantidade de regras por IP. ex.: 4, 8, 16, 32 (Máscara subrede)
-o                                           Nome do arquivo que será salvo as regras de CGNAT.
-m                                           Gera regras para Mikrotik RouterOS.
-i                                           Gera regras para iptables linux.
-n                                           Gera regras para nftables linux.
-h                                           Mostra essa ajuda.\n\n\n

Ex:
php cgnat2.php -c 100.100.0.0 -s 191.241.148.0 -e 191.241.149.255 -t 8 -o rc.nat_popeye -i
php cgnat2.php -c 100.101.0.0 -s 186.235.164.0 -e 186.235.165.255 -t 8 -o rc.nat_popeye1 -i
php cgnat2.php -c 100.102.0.0 -s 186.235.170.0 -e 186.235.171.255 -t 8 -o rc.nat_popeye2 -i
php cgnat2.php -c 100.103.0.0 -s 186.235.174.0 -e 186.235.175.255 -t 8 -o rc.nat_popeye3 -i
php cgnat2.php -c 100.104.0.0 -s 191.241.152.0 -e 191.241.153.255 -t 8 -o rc.nat_popeye4 -i
php cgnat2.php -c 100.105.0.0 -s 186.235.172.0 -e 186.235.173.255 -t 8 -o rc.nat_popeye5 -i
php cgnat2.php -c 100.106.0.0 -s 191.241.156.0 -e 191.241.157.255 -t 8 -o rc.nat_popeye6 -i
php cgnat2.php -c 100.107.0.0 -s 186.235.166.0 -e 186.235.167.255 -t 8 -o rc.nat_popeye7 -i
php cgnat2.php -c 100.108.0.0 -s 191.241.158.0 -e 191.241.159.255 -t 8 -o rc.nat_popeye8 -i
php cgnat2.php -c 100.109.0.0 -s 191.241.150.0 -e 191.241.151.255 -t 8 -o rc.nat_popeye9 -i
php cgnat2.php -c 100.110.0.0 -s 191.241.154.0 -e 191.241.155.255 -t 8 -o rc.nat_popeye10 -i

EOF;
    exit($help);
}
function output($file, $line)
{
    $output = fopen($file, 'a');
    fwrite($output, $line . "\n");
    fclose($output);
}
if (isset($options['h'])) {
    _print_help();
}
if (count($argv) < 7) {
    print("-- Quantidade de parametros inválidos.\n\n");
    _print_help();
}
$CGNAT_IP = ip2long($options['c']);
$CGNAT_START = ip2long($options['s']);
$CGNAT_END = ip2long($options['e']);
$CGNAT_RULES = $options['t'];
$CGNAT_RULES_COUNT = $CGNAT_RULES;
$CGNAT_OUTPUT = __DIR__ . DIRECTORY_SEPARATOR . $options['o'];
$subnet = array(
    '4096'  => '/20',
    '2048'  => '/21',
    '1024'  => '/22',
    '512'   => '/23',
    '256'   => '/24',
    '128'   => '/25',
    '64'    => '/26',
    '32'    => '/27',
    '16'    => '/28',
    '8'     => '/29',
    '4'     => '/30',
    '1'     => '/32'
);
if (!in_array($CGNAT_RULES, array_keys($subnet))) {
    exit("-- Quantidade de regras deve ter o tamanho de uma máscara de subrede.\n\n");
}
if (file_exists($CGNAT_OUTPUT)) {
    unlink($CGNAT_OUTPUT);
}
$output_rules = array();
$output_jumps = array();
$x = $y = 1;
if (isset($options['i'])) {
    $output_rules[] = "#!/bin/bash";
    $output_rules[] = "modprobe nf_nat_pptp";
    $output_rules[] = "modprobe nf_nat_proto_gre";
    $output_rules[] = "modprobe nf_conntrack_pptp";
    $output_rules[] = "modprobe nf_conntrack_proto_gre";
}
for ($i = 0; $i <= ($CGNAT_END - $CGNAT_START); ++$i) {
    $ip = long2ip($CGNAT_START + $i);
    $public = explode('.', $ip);
    $cgnat = explode('.', long2ip($CGNAT_IP));
    if (isset($options['i'])) {
        $output_jumps[] = "/sbin/iptables -w -t nat -A POSTROUTING -s " . long2ip($CGNAT_IP) . "{$subnet[$CGNAT_RULES]} -j CGNAT_{$public[2]}_{$public[3]}_OUT";
        $output_jumps[] = "/sbin/iptables -w -t nat -A PREROUTING -d {$ip}/32 -j CGNAT_{$public[2]}_{$public[3]}_IN";
        $output_rules[] = "/sbin/iptables -w -t nat -N CGNAT_{$public[2]}_{$public[3]}_OUT";
        $output_rules[] = "/sbin/iptables -w -t nat -N CGNAT_{$public[2]}_{$public[3]}_IN";
        $output_rules[] = "/sbin/iptables -w -t nat -F CGNAT_{$public[2]}_{$public[3]}_OUT";
        $output_rules[] = "/sbin/iptables -w -t nat -F CGNAT_{$public[2]}_{$public[3]}_IN";
    }
    if ($public[3] >= 0 && $public[3] <= 255) {
        $ports = ceil(((65536 - 0) / $CGNAT_RULES) - 1);
        $ports2 = ceil((65536 - 0) / $CGNAT_RULES);
        $ports_start = 0;
        $ports_end = $ports_start + $ports;
        if ($ports_start == 0) {
            $ports_start = 1;
        }
        for ($j = $x; $j <= $CGNAT_RULES_COUNT; $j++) {
            $e_cgnat = explode('.', long2ip($CGNAT_IP));
            if ($e_cgnat[3] >= 0 && $e_cgnat[3] <= 255) {
                if (isset($options['i'])) {
                    $output_rules[] = "/sbin/iptables -w -t nat -A CGNAT_{$public[2]}_{$public[3]}_OUT -s " . long2ip($CGNAT_IP) . " -p tcp -j SNAT --to {$ip}:{$ports_start}-{$ports_end}";
                    $output_rules[] = "/sbin/iptables -w -t nat -A CGNAT_{$public[2]}_{$public[3]}_OUT -s " . long2ip($CGNAT_IP) . " -p udp -j SNAT --to {$ip}:{$ports_start}-{$ports_end}";
                    $output_rules[] = "/sbin/iptables -w -t nat -A CGNAT_{$public[2]}_{$public[3]}_IN -d {$ip} -p tcp --dport {$ports_start}:{$ports_end} -j DNAT --to " . long2ip($CGNAT_IP);
                    $output_rules[] = "/sbin/iptables -w -t nat -A CGNAT_{$public[2]}_{$public[3]}_IN -d {$ip} -p udp --dport {$ports_start}:{$ports_end} -j DNAT --to " . long2ip($CGNAT_IP);
                    if ($ports_start == 1) {
                        $first_ip = long2ip($CGNAT_IP);
                    }
                }
                $ports_start = $ports_end + 1;
                $ports_end += $ports2;
                if ($ports_end > 65535) {
                    $ports_end = 65535;
                }
            }
            $CGNAT_IP++;
            $y++;
        }
        $x = $y;
        $CGNAT_RULES_COUNT += $CGNAT_RULES;
    }
    if (isset($options['i'])) {
        $output_rules[] = "/sbin/iptables -w -t nat -A CGNAT_{$public[2]}_{$public[3]}_OUT -j SNAT --to {$ip}";
        $output_rules[] = "/sbin/iptables -w -t nat -A CGNAT_{$public[2]}_{$public[3]}_IN -d {$ip} -j DNAT --to $first_ip";
    }
}
foreach ($output_rules as $o) {
    output($CGNAT_OUTPUT, $o);
}
foreach ($output_jumps as $o) {
    output($CGNAT_OUTPUT, $o);
}
