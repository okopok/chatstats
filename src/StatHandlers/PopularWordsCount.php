<?php


namespace ChatStats\StatHandlers;


use ChatStats\Entity\Message;
use Str\Str;
use Tightenco\Collect\Support\Collection;
use function collect;
use function in_array;
use function mb_strlen;

class PopularWordsCount extends AbstractHandler
{
    protected const STOP_WORDS = 'com, http, htps, htp, net, jpg, png, gif, https, она, oни, без, был, вас, вон, вот, все, всем, где, где-то, для, его, если, есть, еще, ещё, или, как, кто, меня, тебя, было, себя, вам, были, был, было, будет, ему, мне, мной, мое, мой, нам, нас, нет, они, оно, под, пре, при, про, раз, так, там, тебе, тоже, тут, уже, чем, что, чтобы, эти, это, этом, этот, эту, advice, stsadvicebot, вроде, только, просто, надо, может, прям, можно, когда, щас, даже, сегодня, тогда, вобще, точно, кажется, какой, теперь, была, кстати, такой, норм, ага, помню,  потом, такое, быть, пока, хотя, больше, чтоб, ладно, него, наверное, какие, вроде, только, просто, надо, может, прям, можно, почему, когда, щас, даже, типа, ваще, сегодня, тогда, вобще, точно, кажется, какой, блин, была, кстати, норм, ага, потом, быть, пока, хочу, хотя, больше, чтоб, ладно, день, зачем, знаю, него, наверное, потому, извините, понял, поняла, какие, могу, короче, лучше';
    public const WORD_LEN_MIN = 3;
    protected Collection $resultList;
    protected Collection $resultWordTotal;
    protected Collection $resultUserWordTotal;
    protected array $wordList = [
        'ну' => ['ну'],
        'как бы' => ['как-бы', 'какбы', 'как бы'],
        'бы' => ['бы'],
        'сорян' => ['сорян', 'сорйан', 'cарян'],
        'Зануда' => ['зануда'],
        'бар' => ['бар', "бары", "баре", "барчик"],
        'крафт' => ['крафт', "крафтуха", "крафтухи", "крафтец"],
        'поход' => ["поход", "походе", "походы", "походу", "похода", "походом"],
        'чгк' => ["чгк"],
        'мэр' => ["мэр"],
        'лол' => ["лол"],
        'майонез' => ['мазик', 'майонез', 'майонезный', 'мазика', 'мазику', 'маинез'],
    ];
    /**
     * @var string|Str
     */
    protected array $stopWords = [];
    private Collection $uniqWords;

    public function getKey(): string
    {
        return 'popularWordsCount';
    }

    public function getDescription(): string
    {
        return 'Популярные слова';
    }

    protected function exec(): array
    {
        $stopWords = new Str(self::STOP_WORDS);
        $this->stopWords = $stopWords->toLowerCase()->split(', ');

        $this->resultWordTotal = collect([]);
        $this->resultUserWordTotal = collect([]);
        $this->uniqWords = collect([]);

        $emptyKeys = collect($this->wordList)->mapWithKeys(function ($value, $key) {
            return [$key => 0];
        })->all();

        $this->resultList = collect($this->wordList)->mapWithKeys(function ($value, $key) {
            return [$key => collect([])];
        });

        $this->messages->filter(function (Message $message) {
            return mb_strlen($message->text);
        })->each(function (Message $message) use ($emptyKeys) {

            $words = $this->prepareString($message->text);

            $this->countWords($words);
            $this->countUserWord($words, $message->from->username);
            $this->countUserWordList($message, $words, $emptyKeys);
        });
        $this->countUniqWordsByUser();
        return [
            'list' => $this->resultList
                ->mapWithKeys(static function ($value, $key) {
                    return [$key => $value->sortDesc()];
                })
                ->filter(function ($item, $key) {
                    return collect($item)->filter()->isNotEmpty();
                })->all(),
            'total' => $this->resultWordTotal->sortDesc()->take(100)->all(),
            'users' => $this->resultUserWordTotal->mapWithKeys(static function ($value, $key) {
                return [$key => $value->sortDesc()->take(50)->filter(static function ($value) {
                    return $value > 1;
                })->all()];
            })->all(),
            'uniq' => $this->uniqWords->sort()->all()
        ];
    }

    private function prepareString(string $text): array
    {

        $strObj = new Str($text);
        $strObj->toLowerCase()
            ->replace('ё', 'е')
            ->regexReplace('(<a\b[^>]*>.*?<\/a>)', '')
            ->regexReplace('(.+?)\1+', '\1');

        foreach ($this->wordList as $key => $aliases) {
            $strObj->regexReplace($key, $aliases[0]);
        }

        return $strObj
            ->regexReplace('([^0-9a-zа-я\s])+', ' ')
            ->trim()
            ->words();
    }

    private function countWords(array $words): void
    {
        foreach ($words as $word) {
            if ($this->checkStopWord($word) || (new Str($word))->length() < self::WORD_LEN_MIN) {
                continue;
            }
            $wordStat = $this->resultWordTotal->get($word, 0);
            $wordStat++;
            $this->resultWordTotal[$word] = $wordStat;
        }
    }

    private function checkStopWord(string $word): bool
    {
        return in_array($word, $this->stopWords, false);
    }

    private function countUserWord(array $words, string $userName): void
    {
        foreach ($words as $word) {
            if ($this->checkStopWord($word) || (new Str($word))->length() < self::WORD_LEN_MIN) {
                continue;
            }

            $userWords = $this->resultUserWordTotal->get($userName, collect([]));
            $wordStat = $userWords->get($word, 0);
            $wordStat++;

            $userWords->offsetSet($word, $wordStat);
            $this->resultUserWordTotal->offsetSet($userName, $userWords);

            if (!$this->uniqWords->has($word)) {
                $this->uniqWords[$word] = collect([]);
            }
            $this->uniqWords[$word]->push($userName);
        }
    }

    private function countUserWordList(Message $message, array $words, array $emptyKeys): void
    {
        $current = $emptyKeys;

        foreach ($this->wordList as $key => $aliases) {
            foreach ($aliases as $alias) {
                foreach ($words as $word) {
                    if ($alias == $word) {
                        $current[$key]++;
                    }
                }
            }
        }

        foreach ($current as $key => $value) {
            if (!$value) {
                continue;
            }
            $curVal = $this->resultList->get($key)->get($message->from->username, 0);
            $this->resultList[$key]->offsetSet($message->from->username, $curVal + $value);
        }
    }

    private function countUniqWordsByUser(): void
    {
        $result = collect([]);

        $this->uniqWords->filter(static function ($value) {
            return $value->unique()->count() === 1;
        })
            ->mapWithKeys(static function ($value, $key) {
                return [$key => $value[0]];
            })
            ->each(function ($user, $word) use ($result) {
                if (!$result->has($user)) {
                    $result->offsetSet($user, collect([$word => $this->resultWordTotal[$word]]));
                } else {
                    $result[$user]->offsetSet($word, $this->resultWordTotal[$word]);
                }
            });

        $this->uniqWords = $result->sort()->mapWithKeys(static function ($value, $key) {
            return [$key => $value->sortDesc()->take(10)->all()];
        });
    }
}
