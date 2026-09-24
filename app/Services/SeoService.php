<?php
namespace PuneMirror\Services;

final class SeoService
{
    public function __construct(private readonly array $config){}

    public function site(string $title='Pune Mirror Now',string $description='Pune news, neighbourhood updates, traffic, civic, live and developing stories from Pune Mirror Now.',?string $path=null):array
    {
        return [
            'title'=>$title,
            'description'=>$description,
            'canonical'=>pm_absolute_url($path??'/'),
            'image'=>pm_absolute_url('/media/home_rain.jpg'),
            'type'=>'website',
            'robots'=>'index,follow,max-image-preview:large',
            'json_ld'=>null,
        ];
    }

    public function privatePage(string $title,string $path):array
    {
        $seo=$this->site($title,'Your personal Pune Mirror Now page.',$path);
        $seo['robots']='noindex,nofollow';
        return $seo;
    }

    public function story(array $story):array
    {
        $headline=trim((string)($story['headline']??'Pune news'));
        $description=trim((string)($story['deck']??$story['summary']??''));
        if($description==='')$description='Latest Pune news and local updates from Pune Mirror Now.';
        $image=pm_absolute_url(pm_media($story['media'][0]??null));
        $canonical=pm_absolute_url(pm_story_path($story));
        $section=(string)($story['categories'][0]['name']??'Pune');
        $author=(string)($story['author']??'Pune Mirror Desk');
        $published=(string)($story['published_at']??$story['created_at']??gmdate('c'));
        $modified=(string)($story['updated_at']??$published);
        return [
            'title'=>$headline.' | Pune Mirror Now',
            'description'=>$description,
            'canonical'=>$canonical,
            'image'=>$image,
            'type'=>'article',
            'robots'=>'index,follow,max-image-preview:large',
            'json_ld'=>[
                '@context'=>'https://schema.org',
                '@type'=>'NewsArticle',
                'headline'=>$headline,
                'description'=>$description,
                'mainEntityOfPage'=>$canonical,
                'datePublished'=>$published,
                'dateModified'=>$modified,
                'articleSection'=>$section,
                'author'=>['@type'=>'Person','name'=>$author],
                'publisher'=>['@type'=>'Organization','name'=>'Pune Mirror'],
                'image'=>[$image],
            ],
        ];
    }

    public function topic(string $kind,string $name,string $slug):array
    {
        $label=$kind==='area'?'News from '.$name:$name.' news';
        return $this->site(
            $label.' | Pune Mirror Now',
            'Latest '.$label.', live updates and local reporting from Pune Mirror Now.',
            '/'.$kind.'/'.$slug
        );
    }
}
