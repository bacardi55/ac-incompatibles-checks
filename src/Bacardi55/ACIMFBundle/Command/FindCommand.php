<?php
namespace Bacardi55\ACIMFBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelector;

class FindCommand extends Command
{
    //TODO:
    // - over ssh
    // - yaml configuration
    protected $incompatibleUrl;
    protected $warningUrl;
    protected $baseUrl;

    protected function configure()
    {
        $this
          ->setName('ACIMF:find')
            //->setDescription('Initialize web project')
            ->addArgument('rootDir', InputArgument::REQUIRED, 'Root Directory ?')
        ;

        $this->incompatibleUrl = 'https://docs.acquia.com/articles/module-incompatibilities-acquia-cloud';
        $this->warningUrl = 'https://docs.acquia.com/articles/module-list-acquia-cloud-caution';
        $this->baseUrl = 'https://docs.acquia.com';
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $rootDir = $input->getArgument('rootDir');

        $modules = $this->getModules();

        $finder = new Finder();
        $finder->directories()->in($rootDir)->filter(function (\SplFileInfo $file) use ($modules) {
            return (in_array($file->getFilename(), $modules['incompatibles']['modules']));
        });

        foreach ($finder as $file) {
            $errors['incompatibles'][] = $file->getRelativePathname();
        }

        $finder = new Finder();
        $finder->directories()->in($rootDir)->filter(function (\SplFileInfo $file) use ($modules) {
            return (in_array($file->getFilename(), $modules['warnings']['modules']));
        });

        foreach ($finder as $file) {
            $errors['warnings'][] = $file->getRelativePathname();
        }

        foreach ($errors as $type => $error) {
            $output->writeln('<info>' . $type . '</info>');
            foreach ($error as $a) {
                $output->writeln('<error>' . $a . '</error>');
            }
        }
    }

    protected function getModules()
    {
        $incompatibles = $this->getModulesNameFromUrl(
            $this->incompatibleUrl, $this->baseUrl
        );
        $warnings = $this->getModulesNameFromUrl(
            $this->warningUrl, $this->baseUrl
        );

        return [
            'incompatibles' => [
                'message' => 'Incompatibles modules : %modules',
                'modules' => $incompatibles,
            ],
            'warnings' => [
                'message' => 'warnings modules : %modules',
                'modules' => $warnings,
            ],
        ];
    }

    protected function getModulesNameFromUrl($url, $baseUrl) {
        $html = file_get_contents($url);
        $crawler = new Crawler($html, $baseUrl);

        $modules = $crawler->filter('table tr td:first-child a')
            ->each(function (Crawler $node, $i) {
              return end(explode('/', $node->link()->getUri()));
            });

        return $modules;
    }
}

