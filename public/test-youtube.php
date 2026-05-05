<?php
// Test de l'appel Python
$pythonPath = 'C:\\Users\\graci\\MindTrack\\venv\\Scripts\\python.exe';
$scriptPath = 'C:\\Users\\graci\\MindTrack\\python\\youtube_recommender.py';
$action = 'random';

$command = sprintf('"%s" "%s" %s', $pythonPath, $scriptPath, $action);
exec($command, $output, $returnCode);

echo "<h2>Test d'appel Python depuis PHP</h2>";
echo "<strong>Commande:</strong> " . htmlspecialchars($command) . "<br>";
echo "<strong>Code retour:</strong> $returnCode<br>";
echo "<strong>Output brut:</strong><br>";
echo "<pre>";
print_r($output);
echo "</pre>";

if ($returnCode === 0 && !empty($output)) {
    $jsonString = implode('', $output);
    $data = json_decode($jsonString, true);
    echo "<strong>JSON décodé:</strong><br>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<strong style='color:red'>Erreur JSON: " . json_last_error_msg() . "</strong>";
    }
} else {
    echo "<strong style='color:red'>Erreur d'exécution ou output vide</strong>";
}
?>