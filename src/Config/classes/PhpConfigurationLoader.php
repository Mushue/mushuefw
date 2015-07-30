<?php

/**
 * Description of PhpConfigurationLoader
 *
 * @author mushu_000
 */
class PhpConfigurationLoader implements IConfigurationLoader {

    /**
     * {@inheritdoc}
     */
    public function isSupported(\SplFileInfo $source) {
        return 'php' === strtolower($source->getExtension());
    }

    /**
     * {@inheritdoc}
     */
    public function load(\SplFileInfo $source, array $params = []) {
        $file = $source->getPathname();

        if (!is_file($file) || !is_readable($file)) {
            throw new \RuntimeException(sprintf('Unable to load configuration file: "%s"', $file));
        }

        return $this->includeConfigFile($file, $params);
    }

    /**
     * Extracts given params into local scope and includes the config file.
     *
     * @param string $file
     * @param array<string, mixed> $params
     */
    protected function includeConfigFile() {
        extract(func_get_arg(1));

        return (array) require func_get_arg(0);
    }

}
