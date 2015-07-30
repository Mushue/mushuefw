<?php

/**
 *
 * @author mushu_000
 */
interface IConfigurationLoader {

    /**
     * Check if the loader can load the given file.
     * 
     * @param \SplFileInfo $source
     * @return boolean
     */
    public function isSupported(\SplFileInfo $source);

    /**
     * Load config data from the given file and convert it into an array.
     * 
     * @param \SplFileInfo $source
     * @param array<string, mixed> $params
     * @return array
     */
    public function load(\SplFileInfo $source, array $params = []);
}
