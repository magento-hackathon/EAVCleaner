<?php

namespace FIREGENTO\Magento\Command\Diff;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigValuesCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('diff:configvalues')
            ->setDescription('test 123');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input  = $input;
        $this->_output = $output;
        $this->_info('Start Compare ConfigValues');
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $magentoExportDir = \Mage::getBaseDir('export');
            $currentValues = \Mage::app()->getConfig()->getNode('default');
            if (!$currentValues) {
                throw new \InvalidArgumentException('xpath was not found');
            }

            // new Values
            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($currentValues->asXml());
            $currentValues = $this->xml_to_array($dom);

            // oldValues

            $domOldValues = new \DOMDocument();
            $domOldValues->preserveWhiteSpace = false;
            $domOldValues->formatOutput = true;
            $domOldValues->loadXML(file_get_contents($magentoExportDir. DS. 'configvaluescompare.xml'));
            $oldValues = $this->xml_to_array($domOldValues);

            // Compare
            $diffValues = $this->arrayRecursiveDiff($currentValues,$oldValues);
            if(count($diffValues) > 0) {
                $this->output($diffValues,$output);
                file_put_contents($magentoExportDir. DS. 'configvaluescompare.xml', $dom->saveXML());
            }
        }
    }

    /**
     * @param $diffValues
     * @param $output
     *
     * @return void
     */
    private function output($diffValues,$output) {
        foreach($diffValues as $key => $value) {
            $output->write($key.'/');
            if(is_array($value)) $this->output($value,$output);
            else $output->writeln(' = '.$value);
        }

    }


    /**
     * source: http://stackoverflow.com/questions/14553547/what-is-the-best-php-dom-2-array-function
     * @param $root docDocument
     *
     * @return array
     */
    private function xml_to_array($root) {
    $result = array();

    if ($root->hasAttributes()) {
        $attrs = $root->attributes;
        foreach ($attrs as $attr) {
            $result['@attributes'][$attr->name] = $attr->value;
        }
    }

    if ($root->hasChildNodes()) {
        $children = $root->childNodes;
        if ($children->length == 1) {
            $child = $children->item(0);
            if ($child->nodeType == XML_TEXT_NODE) {
                $result['_value'] = $child->nodeValue;
                return count($result) == 1
                    ? $result['_value']
                    : $result;
            }
        }
        $groups = array();
        foreach ($children as $child) {
            if (!isset($result[$child->nodeName])) {
                $result[$child->nodeName] = $this->xml_to_array($child);
            } else {
                if (!isset($groups[$child->nodeName])) {
                    $result[$child->nodeName] = array($result[$child->nodeName]);
                    $groups[$child->nodeName] = 1;
                }
                $result[$child->nodeName][] = $this->xml_to_array($child);
            }
        }
    }

    return $result;
}

    /**
     * source: http://stackoverflow.com/questions/3876435/recursive-array-diff
     *
     * @param $aArray1
     * @param $aArray2
     *
     * @return array
     */
    private function arrayRecursiveDiff($aArray1, $aArray2)
    {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
    }
}
