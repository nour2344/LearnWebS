<?php
namespace App\Twig;

use App\Repository\SchoolSettingRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SchoolExtension extends AbstractExtension
{
    public function __construct(private SchoolSettingRepository $repo) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('school_setting', [$this, 'getSetting']),
        ];
    }

    public function getSetting()
    {
        return $this->repo->findOneBy([]); // singleton
    }
}
