<?php

declare(strict_types=1);

namespace Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

//https://symfony.com/doc/current/components/yaml.html
//https://symfony.com/doc/current/doctrine/reverse_engineering.html
//doctrine:mapping:import

class CreateCrudCommand extends ContainerAwareCommand
{

    const DUMMY_UPPER = 'DUMMY_UPPER';
    const DUMMY_LOWER = 'DUMMY_LOWER';
    const MODEL_UPPER = 'MODEL_UPPER';

    protected function configure()
    {
        $this
            ->setName('tianos:create:crud')
            ->setDescription('Crud capitalized')
            ->addOption('baz', 'tn', InputOption::VALUE_NONE, 'Test option')
//            ->addArgument('status', InputArgument::OPTIONAL, 'El status: e.g. TRUE - FALSE', false)
            ->addArgument('bundle', InputArgument::OPTIONAL, 'The component: product, client', false)
        ;
    }

    /**
     * Execute the command
     * The environment option is automatically handled.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundle = $input->getArgument('bundle');
        $bundleLower = strtolower($bundle);
        $bundle = ucfirst($bundleLower);

        $output->writeln([
            '<comment>=========== <question>CRUD creator:</question> proceso ... ===========</comment>',
            '--',
        ]);

        $cwd = getcwd();
        $src = $cwd . '/src/Bundle/'.self::DUMMY_UPPER.'Bundle';
        $dest = $cwd . '/src/Bundle/' . $bundle . 'Bundle';
        $srcComponent = $cwd.'/src/Component/'.self::DUMMY_UPPER;
        $destComponent = $cwd.'/src/Component/'.$bundle;



        //=============================================
        $output->writeln('');
        $output->writeln('<question>CRUD: bundle</question>');

        $result = $this->linuxCommand('cp -R '.$src.' '. $dest);
        $output->writeln('<info>* Copiar bundle: '.$result.'</info>');

        $result = $this->linuxCommand('chown -R 1000:1000 '. $dest);
        $output->writeln('<info>* Dar permisos bundle: '.$result.'</info>');

        $result = $this->recurseRenameDirectory($dest, self::DUMMY_UPPER, $bundle);
        $output->writeln('<info>* Cambiar nombre a folderes: '.$result.'</info>');

        $result = $this->recurseRenameFiles($dest, self::DUMMY_UPPER, $bundle);
        $output->writeln('<info>* Cambiar nombre a files: '.$result.'</info>');

        $result = $this->linuxCommand('find '.$dest.' -name \*.php -exec sed -i "s/'.self::DUMMY_LOWER.'/'.$bundleLower.'/g" {} \;');
        $result = $this->linuxCommand('find '.$dest.' -name \*.php -exec sed -i "s/'.self::DUMMY_UPPER.'/'.$bundle.'/g" {} \;');
        $output->writeln('<info>* Cambiar texto PHP dentro del bundle: '.$result.'</info>');

        $result = $this->linuxCommand('find '.$dest.' -name \*.yml -exec sed -i "s/'.self::DUMMY_LOWER.'/'.$bundleLower.'/g" {} \;');
        $result = $this->linuxCommand('find '.$dest.' -name \*.yml -exec sed -i "s/'.self::DUMMY_UPPER.'/'.$bundle.'/g" {} \;');
        $output->writeln('<info>* Cambiar texto YML dentro del bundle: '.$result.'</info>');



        //=============================================
        $output->writeln('');
        $output->writeln('<question>CRUD: component</question>');
        $result = $this->linuxCommand('cp -R '.$srcComponent.' '. $destComponent);
        $output->writeln('<info>* Copiar bundle: '.$result.'</info>');

        $result = $this->linuxCommand('chown -R 1000:1000 '. $destComponent);
        $output->writeln('<info>* Dar permisos bundle: '.$result.'</info>');

        $result = $this->recurseRenameDirectory($destComponent, self::DUMMY_UPPER, $bundle);
        $output->writeln('<info>* Cambiar nombre a folderes: '.$result.'</info>');

        $result = $this->recurseRenameFiles($destComponent, self::DUMMY_UPPER, $bundle);
        $output->writeln('<info>* Cambiar nombre a files: '.$result.'</info>');

        $result = $this->linuxCommand('find '.$destComponent.' -name \*.php -exec sed -i "s/'.self::DUMMY_UPPER.'/'.$bundle.'/g" {} \;');
        $output->writeln('<info>* Cambiar texto PHP dentro del bundle: '.$result.'</info>');



        //=============================================
        $output->writeln('');
        $output->writeln('<question>CRUD: services</question>');
        $result = $this->crudServices($bundle);
        $output->writeln('<info>* services YML bundle: '.$result.'</info>');



        //=============================================
        $output->writeln('--');
        $output->writeln('<comment>=== Se termino el proceso ===</comment>');
        $output->writeln('--');
    }

    private function linuxCommand($command) {

        $process = new Process($command);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    private function recurseRenameDirectory($src, $needle, $bundle) {

        $dir = opendir($src);

        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {

                $path = $src . '/' . $file;

                if ( is_dir($path) ) {

                    $this->recurseRenameDirectory($path, $needle, $bundle);

                    $explode = explode(DIRECTORY_SEPARATOR, $path);
                    $end = end($explode);

                    if (strpos($end, $needle) !== false) {

                        $newname = str_replace($needle, $bundle, $path);

                        rename($path, $newname);
                    }
                }
            }
        }

        closedir($dir);
    }

    private function recurseRenameFiles($src, $needle, $bundle) {

        $dir = opendir($src);

        while(false !== ( $file = readdir($dir)) ) {

            if (( $file != '.' ) && ( $file != '..' )) {

                $path = $src . '/' . $file;

                if ( is_dir($path) ) {
                    $this->recurseRenameFiles($path, $needle, $bundle);
                }
                else {

                    $explode = explode(DIRECTORY_SEPARATOR, $path);
                    $end = end($explode);

                    if (strpos($end, $needle) !== false) {
                        $newname = str_replace($needle, $bundle, $path);

                        rename($path, $newname);
                    }
                }
            }
        }

        closedir($dir);
    }

    private function crudServices($bundle)
    {
        # https://symfony.com/doc/current/service_container.html
        $exist = false;
        $servicesSubfix = 'Bundle/Resources/config/services.yml';
        $ymlPath = __DIR__ . '/../../../../app/config/services/services.yml';

        $ymlValues = Yaml::parseFile($ymlPath);

        foreach ($ymlValues['imports'] as $key => $ymlValue){

            $ymlValue = array_shift($ymlValue);
            $ymlValue = substr($ymlValue, 1);
            $ymlValue = str_replace($servicesSubfix, '', $ymlValue);

            if($ymlValue == $bundle){
                $exist = true;
            }
        }

        if(!$exist){
            $ymlValues['imports'][]['resource'] = '@'.$bundle.$servicesSubfix;
        }

        //COPIAR YML
        file_put_contents($ymlPath, Yaml::dump($ymlValues));
    }

}

