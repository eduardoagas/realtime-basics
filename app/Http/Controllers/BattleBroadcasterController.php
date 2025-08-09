namespace App\Http\Controllers;

use App\Services\Battle\BattleBroadcaster;

class BattleController extends Controller
{
protected BattleBroadcaster $broadcaster;

public function __construct(BattleBroadcaster $broadcaster)
{
$this->broadcaster = $broadcaster;
}

public function sendBattleUpdate(string $battleId, array $data)
{
$this->broadcaster->broadcastToBattle($battleId, $data);

return response()->json(['status' => 'ok']);
}
}