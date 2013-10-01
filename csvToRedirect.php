<?php
class csvToRedirect
{
    private $_data;
    private $_sorted;
    private $_output;
    private $_target;

    public function __construct($target)
    {
        $this->_target = $target;
    }

    protected function parseCsv()
    {
        if ($file = fopen($this->_target, 'r')) {
            while (!feof($file)) {
                $row = fgetcsv($file);

                $this->_data[] = [$row[0], $row[1]];
            }
        }
    }

    protected function cleanRedirects()
    {
        if (is_null($this->_data)) {
            throw new Exception('Nothing to clean!');
        }

        $find    = ['%\?prev=[0-9]+?&%', '%\?next=[0-9]+?&%', '%&next[0-9]+=(.*)+%', '%&prev[0-9]+=(.*)+%'];
        $replace = ['?', '?', '', ''];

        foreach ($this->_data as $i => $v) {
            $this->_data[$i] = [preg_replace($find, $replace, $v[0]), $v[1]];
        }
        return true;
    }

    protected function sortRedirects()
    {
        if (is_null($this->_data)) {
            throw new Exception('Nothing to sort!');
        }

        foreach ($this->_data as $v) {
            if (preg_match('%^/imc-product.cfm(.*)?%', $v[0])) {
                $this->_sorted['imc-product'][] = [$v[0], $v[1]];
            }

            if (preg_match('%^/imc-product-cat.cfm(.*)?%', $v[0])) {
                $this->_sorted['imc-product-cat'][] = [$v[0], $v[1]];
            }
        }
        return true;
    }

    protected function buildOutput()
    {
        foreach($this->_sorted['imc-product'] as $v){
            $vars = preg_replace(['%^/imc-product.cfm\?%'], '', $v[0]);
            $this->_output .= 'RewriteCond %{REQUEST_URI} ^/imc-product.cfm$'."\r\n";
            $this->_output .= 'RewriteCond %{QUERY_STRING} ^'.$vars."$\r\n";
            $this->_output .= 'RewriteRule ^.*$ ' . $v[1] . '? [L,R=301]' . "\r\n";
        }
    }


    protected function saveOutput()
    {
        $handle = fopen('output.txt', 'w');
        fwrite($handle, $this->_output);
    }

    public function run()
    {
        $this->parseCsv();
        $this->cleanRedirects();
        $this->sortRedirects();
        $this->buildOutput();
        $this->saveOutput();
    }
}

$run = new csvToRedirect('redirectstoparse.csv');
$run->run();