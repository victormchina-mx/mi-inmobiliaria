<?php

namespace Tests\Feature\Recepcion;

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class RecepcionTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $asesor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('media');
        Filament::setCurrentPanel('taller');

        $this->asesor = $this->usuario(Rol::Asesor);
        $this->actingAs($this->asesor);
    }

    protected function usuario(Rol $rol): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->assignRole($rol->value));
    }

    /**
     * PNG con trazos que simula una firma real (más de 1.5 KB).
     */
    protected function firmaPng(): string
    {
        $img = imagecreatetruecolor(500, 180);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        mt_srand(7);
        for ($i = 0; $i < 400; $i++) {
            imagesetpixel($img, mt_rand(0, 499), mt_rand(0, 179), imagecolorallocate($img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        $negro = imagecolorallocate($img, 0, 0, 0);
        for ($i = 0; $i < 40; $i++) {
            imageline($img, mt_rand(0, 499), mt_rand(0, 179), mt_rand(0, 499), mt_rand(0, 179), $negro);
        }
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
