
// SolarAPP+ Interview Routes
use App\Http\Controllers\Api\AhjController;
Route::post('/partners/installers', [AhjController::class, 'store']);
