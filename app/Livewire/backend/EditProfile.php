<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use App\Models\ServiceProfile;
use App\Models\Service;
use App\Models\PortfolioItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

#[Layout('components.layouts.app')]
#[Title('My Marketplace Profile')]
class EditProfile extends Component
{
    use WithFileUploads;

    public string $tab = 'profile';

    // ── Profile fields ────────────────────────────────────────
    public string  $headline           = '';
    public string  $bio                = '';
    public array   $skills             = [];
    public string  $newSkill           = '';
    public array   $languages          = [];
    public string  $location           = '';
    public string  $availability       = 'open_to_work';
    public ?float  $hourlyRate         = null;
    public string  $currency           = 'USD';
    public ?int    $yearsExp           = null;
    public ?int    $responseTime       = 24;
    public string  $category           = '';
    public ?int    $sessionDuration    = 60;

    // ── Service form ──────────────────────────────────────────
    public ?int    $editServiceId      = null;
    public string  $svcTitle           = '';
    public string  $svcDescription     = '';
    public string  $svcCategory        = '';
    public ?float  $svcPriceFrom       = null;
    public ?float  $svcPriceTo         = null;
    public string  $svcCurrency        = 'USD';
    public ?int    $svcDeliveryDays    = null;
    public bool    $showServiceForm    = false;

    // ── Portfolio form ────────────────────────────────────────
    public ?int    $editPortfolioId    = null;
    public string  $ptTitle            = '';
    public string  $ptDescription      = '';
    public string  $ptProjectUrl       = '';
    public string  $ptGithubUrl        = '';
    public array   $ptTechStack        = [];
    public string  $ptNewTech          = '';
    public bool    $ptIsPublic         = true;
    public bool    $ptIsFeatured       = false;
    public         $ptCover            = null;
    public bool    $showPortfolioForm  = false;

    public function mount(): void
    {
        // Enable marketplace if first visit
        $user = Auth::user();
        if (! $user->is_marketplace_enabled) {
            $user->update(['is_marketplace_enabled' => true]);
        }

        $sp = ServiceProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['availability_status' => 'open_to_work', 'avg_rating' => 0, 'total_reviews' => 0, 'session_duration' => 60, 'response_time_hours' => 24, 'currency' => 'USD', 'category' => '', 'skills' => [], 'languages' => [], 'years_experience' => 0, 'headline' => '', 'bio' => '']
        );

        $this->headline        = $sp->headline ?? '';
        $this->bio             = $sp->bio ?? '';
        $this->skills          = (array) ($sp->skills ?? []);
        $this->languages       = (array) ($sp->languages ?? []);
        $this->location        = $sp->location ?? '';
        $this->availability    = $sp->availability_status ?? 'open_to_work';
        $this->hourlyRate      = $sp->hourly_rate;
        $this->currency        = $sp->currency ?? 'USD';
        $this->yearsExp        = $sp->years_experience;
        $this->responseTime    = $sp->response_time_hours ?? 24;
        $this->category        = $sp->profession_category ?? '';
        $this->sessionDuration = $sp->session_duration ?? 60;
    }

    // ── Profile save ──────────────────────────────────────────
    public function saveProfile(): void
    {
        $this->validate([
            'headline'   => ['required','string','max:200'],
            'bio'        => ['nullable','string','max:3000'],
            'hourlyRate' => ['nullable','numeric','min:1','max:10000'],
            'yearsExp'   => ['nullable','integer','min:0','max:50'],
        ]);

        ServiceProfile::where('user_id', Auth::id())->update([
            'headline'           => $this->headline,
            'bio'                => $this->bio ?: null,
            'skills'             => $this->skills,
            'languages'          => $this->languages,
            'location'           => $this->location ?: null,
            'availability_status'=> $this->availability,
            'hourly_rate'        => $this->hourlyRate,
            'currency'           => $this->currency,
            'years_experience'   => $this->yearsExp,
            'response_time_hours'=> $this->responseTime,
            'profession_category'=> $this->category,
            'session_duration'   => $this->sessionDuration,
        ]);

        $this->dispatch('toast', ['message' => 'Profile saved.', 'type' => 'success']);
    }

    public function addSkill(): void
    {
        $s = trim($this->newSkill);
        if ($s && !in_array($s, $this->skills)) {
            $this->skills[] = $s;
        }
        $this->newSkill = '';
    }

    public function removeSkill(int $i): void
    {
        array_splice($this->skills, $i, 1);
    }

    public function toggleLanguage(string $lang): void
    {
        if (in_array($lang, $this->languages)) {
            $this->languages = array_values(array_diff($this->languages, [$lang]));
        } else {
            $this->languages[] = $lang;
        }
    }

    // ── Services ──────────────────────────────────────────────
    public function openServiceForm(?int $id = null): void
    {
        $this->resetServiceForm();
        if ($id) {
            $svc = Service::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
            $this->editServiceId   = $id;
            $this->svcTitle        = $svc->title;
            $this->svcDescription  = $svc->description ?? '';
            $this->svcCategory     = $svc->category;
            $this->svcPriceFrom    = $svc->price_from;
            $this->svcPriceTo      = $svc->price_to;
            $this->svcCurrency     = $svc->currency ?? 'USD';
            $this->svcDeliveryDays = $svc->delivery_days;
        }
        $this->showServiceForm = true;
    }

    public function saveService(): void
    {
        $this->validate([
            'svcTitle'        => ['required','string','max:150'],
            'svcCategory'     => ['required','string'],
            'svcPriceFrom'    => ['nullable','numeric','min:1'],
            'svcPriceTo'      => ['nullable','numeric','min:1','gte:svcPriceFrom'],
            'svcDeliveryDays' => ['nullable','integer','min:1'],
        ]);

        $data = [
            'user_id'      => Auth::id(),
            'title'        => $this->svcTitle,
            'description'  => $this->svcDescription ?: null,
            'category'     => $this->svcCategory,
            'price_from'   => $this->svcPriceFrom,
            'price_to'     => $this->svcPriceTo,
            'currency'     => $this->svcCurrency,
            'delivery_days'=> $this->svcDeliveryDays,
            'is_active'    => true,
        ];

        if ($this->editServiceId) {
            Service::where('id', $this->editServiceId)->where('user_id', Auth::id())->update($data);
        } else {
            Service::create($data);
        }

        $this->resetServiceForm();
        $this->dispatch('toast', ['message' => 'Service saved.', 'type' => 'success']);
    }

    public function toggleService(int $id): void
    {
        $svc = Service::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $svc->update(['is_active' => !$svc->is_active]);
    }

    public function deleteService(int $id): void
    {
        Service::where('id', $id)->where('user_id', Auth::id())->delete();
        $this->dispatch('toast', ['message' => 'Service deleted.', 'type' => 'info']);
    }

    protected function resetServiceForm(): void
    {
        $this->reset(['editServiceId','svcTitle','svcDescription','svcCategory',
                      'svcPriceFrom','svcPriceTo','svcCurrency','svcDeliveryDays','showServiceForm']);
    }

    // ── Portfolio ─────────────────────────────────────────────
    public function openPortfolioForm(?int $id = null): void
    {
        $this->resetPortfolioForm();
        if ($id) {
            $pt = PortfolioItem::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
            $this->editPortfolioId = $id;
            $this->ptTitle         = $pt->title;
            $this->ptDescription   = $pt->description ?? '';
            $this->ptProjectUrl    = $pt->project_url ?? '';
            $this->ptGithubUrl     = $pt->github_url  ?? '';
            $this->ptTechStack     = (array)($pt->tech_stack ?? []);
            $this->ptIsPublic      = (bool)$pt->is_public;
            $this->ptIsFeatured    = (bool)$pt->is_featured;
        }
        $this->showPortfolioForm = true;
    }

    public function savePortfolio(): void
    {
        $this->validate([
            'ptTitle'      => ['required','string','max:200'],
            'ptProjectUrl' => ['nullable','url','max:500'],
            'ptGithubUrl'  => ['nullable','url','max:500'],
            'ptCover'      => ['nullable','image','max:4096'],
        ]);

        $coverPath = null;
        if ($this->ptCover) {
            $coverPath = $this->ptCover->store('portfolio/covers','s3');
        }

        $data = [
            'user_id'     => Auth::id(),
            'title'       => $this->ptTitle,
            'description' => $this->ptDescription ?: null,
            'project_url' => $this->ptProjectUrl  ?: null,
            'github_url'  => $this->ptGithubUrl   ?: null,
            'tech_stack'  => $this->ptTechStack,
            'is_public'   => $this->ptIsPublic,
            'is_featured' => $this->ptIsFeatured,
        ];
        if ($coverPath) $data['cover_image'] = $coverPath;

        if ($this->editPortfolioId) {
            PortfolioItem::where('id', $this->editPortfolioId)->where('user_id', Auth::id())->update($data);
        } else {
            PortfolioItem::create($data);
        }

        $this->resetPortfolioForm();
        $this->dispatch('toast', ['message' => 'Portfolio item saved.', 'type' => 'success']);
    }

    public function deletePortfolio(int $id): void
    {
        $pt = PortfolioItem::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        if ($pt->cover_image) Storage::disk('s3')->delete($pt->cover_image);
        $pt->delete();
        $this->dispatch('toast', ['message' => 'Portfolio item deleted.', 'type' => 'info']);
    }

    protected function resetPortfolioForm(): void
    {
        $this->reset(['editPortfolioId','ptTitle','ptDescription','ptProjectUrl','ptGithubUrl',
                      'ptTechStack','ptNewTech','ptIsPublic','ptIsFeatured','ptCover','showPortfolioForm']);
        $this->ptIsPublic = true;
    }

    // ── Computed ──────────────────────────────────────────────
    #[Computed] public function services()
    {
        return Service::where('user_id', Auth::id())->latest()->get();
    }

    #[Computed] public function portfolioItems()
    {
        return PortfolioItem::where('user_id', Auth::id())->orderByDesc('is_featured')->latest()->get();
    }

    #[Computed] public function completeness(): int
    {
        $score = 0;
        if ($this->headline)   $score += 20;
        if ($this->bio)        $score += 20;
        if (count($this->skills) > 0) $score += 20;
        if ($this->hourlyRate) $score += 20;
        if (Auth::user()->avatar_url) $score += 20;
        return $score;
    }

    public function render()
    {
        return view('livewire.backend.editProfile');
    }
}
