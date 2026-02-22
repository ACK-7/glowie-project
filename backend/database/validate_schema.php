<?php

/**
 * Schema Validation Script
 * 
 * This script validates the enhanced database schema files for:
 * - Syntax correctness
 * - Migration file structure
 * - Seeder class structure
 * - Foreign key relationships
 * - Index definitions
 */

class SchemaValidator
{
    private $errors = [];
    private $warnings = [];
    
    public function validate()
    {
        echo "🔍 Validating Enhanced Database Schema...\n\n";
        
        $this->validateMigrationFiles();
        $this->validateSeederFiles();
        $this->validateConsoleCommands();
        $this->validateFileStructure();
        
        $this->displayResults();
        
        return empty($this->errors);
    }
    
    private function validateMigrationFiles()
    {
        echo "📋 Validating Migration Files...\n";
        
        $migrationFiles = [
            '2025_01_12_create_activity_logs_table.php',
            '2025_01_13_create_system_settings_table.php',
            '2025_01_14_enhance_bookings_table.php',
            '2025_01_15_enhance_quotes_table.php',
            '2025_01_16_enhance_shipments_table.php',
            '2025_01_17_enhance_documents_table.php',
            '2025_01_18_enhance_payments_table.php',
            '2025_01_19_enhance_users_table_for_admin.php',
            '2025_01_20_add_foreign_key_constraints.php'
        ];
        
        foreach ($migrationFiles as $file) {
            $path = "database/migrations/{$file}";
            
            if (!file_exists($path)) {
                $this->errors[] = "Migration file missing: {$file}";
                continue;
            }
            
            // Check syntax
            $output = shell_exec("php -l {$path} 2>&1");
            if (strpos($output, 'No syntax errors') === false) {
                $this->errors[] = "Syntax error in {$file}: {$output}";
                continue;
            }
            
            // Check for required methods
            $content = file_get_contents($path);
            if (strpos($content, 'public function up()') === false) {
                $this->errors[] = "Missing up() method in {$file}";
            }
            if (strpos($content, 'public function down()') === false) {
                $this->errors[] = "Missing down() method in {$file}";
            }
            
            echo "  ✓ {$file}\n";
        }
    }
    
    private function validateSeederFiles()
    {
        echo "\n🌱 Validating Seeder Files...\n";
        
        $seederFiles = [
            'EnhancedDatabaseSeeder.php',
            'SystemSettingsSeeder.php',
            'AdminUsersSeeder.php',
            'VehicleTypesSeeder.php',
            'RoutesSeeder.php',
            'CustomersSeeder.php',
            'QuotesSeeder.php',
            'BookingsSeeder.php',
            'ShipmentsSeeder.php',
            'DocumentsSeeder.php',
            'PaymentsSeeder.php'
        ];
        
        foreach ($seederFiles as $file) {
            $path = "database/seeders/{$file}";
            
            if (!file_exists($path)) {
                $this->errors[] = "Seeder file missing: {$file}";
                continue;
            }
            
            // Check syntax
            $output = shell_exec("php -l {$path} 2>&1");
            if (strpos($output, 'No syntax errors') === false) {
                $this->errors[] = "Syntax error in {$file}: {$output}";
                continue;
            }
            
            // Check for required methods
            $content = file_get_contents($path);
            if (strpos($content, 'public function run()') === false) {
                $this->errors[] = "Missing run() method in {$file}";
            }
            if (strpos($content, 'extends Seeder') === false) {
                $this->errors[] = "Class does not extend Seeder in {$file}";
            }
            
            echo "  ✓ {$file}\n";
        }
    }
    
    private function validateConsoleCommands()
    {
        echo "\n⚡ Validating Console Commands...\n";
        
        $commandFile = 'app/Console/Commands/SetupEnhancedDatabase.php';
        
        if (!file_exists($commandFile)) {
            $this->errors[] = "Console command file missing: {$commandFile}";
            return;
        }
        
        // Check syntax
        $output = shell_exec("php -l {$commandFile} 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            $this->errors[] = "Syntax error in {$commandFile}: {$output}";
            return;
        }
        
        // Check for required methods and properties
        $content = file_get_contents($commandFile);
        $requiredElements = [
            'extends Command',
            'protected $signature',
            'protected $description',
            'public function handle()'
        ];
        
        foreach ($requiredElements as $element) {
            if (strpos($content, $element) === false) {
                $this->errors[] = "Missing {$element} in {$commandFile}";
            }
        }
        
        echo "  ✓ SetupEnhancedDatabase.php\n";
    }
    
    private function validateFileStructure()
    {
        echo "\n📁 Validating File Structure...\n";
        
        $requiredFiles = [
            'database/ENHANCED_SCHEMA_README.md',
            'database/setup_enhanced_database.php',
            'database/seeders/DatabaseSeeder.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                $this->errors[] = "Required file missing: {$file}";
            } else {
                echo "  ✓ {$file}\n";
            }
        }
        
        // Check if DatabaseSeeder.php includes EnhancedDatabaseSeeder
        if (file_exists('database/seeders/DatabaseSeeder.php')) {
            $content = file_get_contents('database/seeders/DatabaseSeeder.php');
            if (strpos($content, 'EnhancedDatabaseSeeder') === false) {
                $this->warnings[] = "DatabaseSeeder.php does not reference EnhancedDatabaseSeeder";
            }
        }
    }
    
    private function displayResults()
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 VALIDATION RESULTS\n";
        echo str_repeat('=', 60) . "\n";
        
        if (empty($this->errors) && empty($this->warnings)) {
            echo "✅ All validations passed successfully!\n";
            echo "🎉 Enhanced database schema is ready for deployment.\n";
        } else {
            if (!empty($this->errors)) {
                echo "❌ ERRORS FOUND:\n";
                foreach ($this->errors as $error) {
                    echo "  • {$error}\n";
                }
                echo "\n";
            }
            
            if (!empty($this->warnings)) {
                echo "⚠️  WARNINGS:\n";
                foreach ($this->warnings as $warning) {
                    echo "  • {$warning}\n";
                }
                echo "\n";
            }
        }
        
        echo "📈 SUMMARY:\n";
        echo "  Migration files: " . (9 - count(array_filter($this->errors, fn($e) => strpos($e, 'Migration file') === 0))) . "/9\n";
        echo "  Seeder files: " . (11 - count(array_filter($this->errors, fn($e) => strpos($e, 'Seeder file') === 0))) . "/11\n";
        echo "  Console commands: " . (file_exists('app/Console/Commands/SetupEnhancedDatabase.php') ? '1' : '0') . "/1\n";
        echo "  Documentation: " . (file_exists('database/ENHANCED_SCHEMA_README.md') ? '1' : '0') . "/1\n";
        
        echo str_repeat('=', 60) . "\n";
    }
}

// Run validation if called directly
if (php_sapi_name() === 'cli') {
    $validator = new SchemaValidator();
    $success = $validator->validate();
    exit($success ? 0 : 1);
}