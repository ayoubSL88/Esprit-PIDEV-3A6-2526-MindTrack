Note: Using configuration file C:\Users\ideaPAD 5\Documents\MindTrack\phpstan.neon.
 111/111 [============================] 100%

 ------ ----------------------------------------------------------------------- 
  Line   Controller\Front\GestionSuiviHabitudes\OverviewController.php          
 ------ ----------------------------------------------------------------------- 
  :200   Offset 'reason' on array{blocked: true, categories: list<string>, rea  
         son: string} on left side of ?? always exists and is not nullable.     
         🪪  nullCoalesce.offset                                                
 ------ ----------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------- 
  Line   Controller\Front\GestionUser\ProfileController.php                     
 ------ ----------------------------------------------------------------------- 
  :366   Strict comparison using !== between (int|false) and null will always   
         evaluate to true.                                                      
         🪪  notIdentical.alwaysTrue                                            
         💡  Because the type is coming from a PHPDoc, you can turn off this    
         check by setting treatPhpDocTypesAsCertain: false in your phpstan.neo  
         n.                                                                     
         💡  If Symfony\Component\HttpFoundation\File\UploadedFile::isValid()   
         is impure, add @phpstan-impure PHPDoc tag above its declaration. Lear  
         n more: https://phpstan.org/blog/remembering-and-forgetting-returned-  
         values                                                                 
 ------ ----------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------- 
  Line   Controller\RegisterController.php                                      
 ------ ----------------------------------------------------------------------- 
  :131   Strict comparison using !== between array{image_id: string, subject:   
         string} and null will always evaluate to true.                         
         🪪  notIdentical.alwaysTrue                                            
         💡  Because the type is coming from a PHPDoc, you can turn off this    
         check by setting treatPhpDocTypesAsCertain: false in your phpstan.neo  
         n.                                                                     
 ------ ----------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------- 
  Line   Service\Chat\OllamaChatService.php                                     
 ------ ----------------------------------------------------------------------- 
  :278   Call to function is_array() with array<string, mixed> will always eva  
         luate to true.                                                         
         🪪  function.alreadyNarrowedType                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this    
         check by setting treatPhpDocTypesAsCertain: false in your phpstan.neo  
         n.                                                                     
 ------ ----------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------- 
  Line   Service\GestionHumeur\EmotionDetectionService.php                      
 ------ ----------------------------------------------------------------------- 
  :44    Call to function is_string() with string will always evaluate to       
         true.                                                                  
         🪪  function.alreadyNarrowedType                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this    
         check by setting treatPhpDocTypesAsCertain: false in your phpstan.neo  
         n.                                                                     
  :201   Call to function is_array() with array<string, float|int> will always  
          evaluate to true.                                                     
         🪪  function.alreadyNarrowedType                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this    
         check by setting treatPhpDocTypesAsCertain: false in your phpstan.neo  
         n.                                                                     
  :201   Offset 'metrics' on array{type: string, label: string, intensity:      
         int, confidence: float, summary: string, metrics: array<string, float  
         |int>, framesAnalyzed: int} on left side of ?? always exists and is n  
         ot nullable.                                                           
         🪪  nullCoalesce.offset                                                
  :235   Strict comparison using === between array{frames:                      
         non-empty-list<array{type: string, label: string, intensity: int,      
         confidence: float, summary: string, metrics: array<string, float|int>  
         , framesAnalyzed: int}>, score: float, voteWeight: float} and false    
         will always evaluate to false.                                         
         🪪  identical.alwaysFalse                                              
         💡  Because the type is coming from a PHPDoc, you can turn off this    
         check by setting treatPhpDocTypesAsCertain: false in your phpstan.neo  
         n.                                                                     
  :251   Call to function is_float() with float will always evaluate to true.   
         🪪  function.alreadyNarrowedType                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this    
         check by setting treatPhpDocTypesAsCertain: false in your phpstan.neo  
         n.                                                                     
  :251   Result of && is always false.                                          
         🪪  booleanAnd.alwaysFalse                                             
         💡  Because the type is coming from a PHPDoc, you can turn off this    
         check by setting treatPhpDocTypesAsCertain: false in your phpstan.neo  
         n.                                                                     
 ------ ----------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------- 
  Line   Service\GestionHumeur\HumeurAnalyticsService.php                       
 ------ ----------------------------------------------------------------------- 
  :100   Instanceof between App\Entity\Humeur and App\Entity\Humeur will        
         always evaluate to true.                                               
         🪪  instanceof.alwaysTrue                                              
         💡  Because the type is coming from a PHPDoc, you can turn off this    
         check by setting treatPhpDocTypesAsCertain: false in your phpstan.neo  
         n.     