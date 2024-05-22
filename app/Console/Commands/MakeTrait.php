<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeTrait extends Command
{
    protected $signature = 'make:trait {name}';

    protected $description = 'Create a new trait';

    public function handle()
    {
        $name = $this->argument('name');
        $traitName = ucfirst($name) . 'Trait';
        $filePath = app_path("Traits/{$traitName}.php");

        if (file_exists($filePath)) {
            $this->error("Trait {$traitName} already exists!");
            return;
        }

        $content = "<?php\n\nnamespace App\Traits;\n\ntrait {$traitName}\n{\n    // Your trait content here\n}\n";

        file_put_contents($filePath, $content);

        $this->info("Trait {$traitName} created successfully!");
    }
}
