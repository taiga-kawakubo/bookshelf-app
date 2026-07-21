<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::query()
            ->pluck('id', 'email');

        $bookIds = Book::query()
            ->pluck('id', 'isbn');

        $reviews = [
            // 吾輩は猫である：3件
            [
                'isbn' => '9784101010014',
                'user_email' => 'suzuki@example.com',
                'rating' => 5,
                'comment' => '猫の視点から人間社会を観察する表現が面白く、最後まで楽しく読めました。',
            ],
            [
                'isbn' => '9784101010014',
                'user_email' => 'tanaka@example.com',
                'rating' => 4,
                'comment' => '文章は少し難しかったですが、人間の性格を風刺する描写が印象に残りました。',
            ],
            [
                'isbn' => '9784101010014',
                'user_email' => 'sato@example.com',
                'rating' => 3,
                'comment' => '時代を感じる表現はありますが、猫から見た人間の姿が興味深かったです。',
            ],

            // 人を動かす：3件
            [
                'isbn' => '9784422100524',
                'user_email' => 'yamada@example.com',
                'rating' => 5,
                'comment' => '相手を尊重して信頼関係を築くための考え方を具体的に学べました。',
            ],
            [
                'isbn' => '9784422100524',
                'user_email' => 'sato@example.com',
                'rating' => 4,
                'comment' => '仕事だけでなく日常の人間関係にも応用できる内容だと感じました。',
            ],
            [
                'isbn' => '9784422100524',
                'user_email' => 'takahashi@example.com',
                'rating' => 5,
                'comment' => '具体例が豊富で、人との接し方を見直すきっかけになりました。',
            ],

            // リーダブルコード：3件
            [
                'isbn' => '9784873115658',
                'user_email' => 'yamada@example.com',
                'rating' => 5,
                'comment' => '変数名や関数の分け方など、すぐに実践できる改善方法を学べました。',
            ],
            [
                'isbn' => '9784873115658',
                'user_email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '読みやすいコードを書くための考え方が具体例とともに整理されていました。',
            ],
            [
                'isbn' => '9784873115658',
                'user_email' => 'tanaka@example.com',
                'rating' => 5,
                'comment' => 'コードは動くだけでなく、他の人が理解できることも重要だと分かりました。',
            ],

            // 7つの習慣：3件
            [
                'isbn' => '9784863940246',
                'user_email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '主体的に行動することの大切さを改めて考えることができました。',
            ],
            [
                'isbn' => '9784863940246',
                'user_email' => 'sato@example.com',
                'rating' => 5,
                'comment' => '目標を明確にして行動するための考え方が体系的にまとめられていました。',
            ],
            [
                'isbn' => '9784863940246',
                'user_email' => 'takahashi@example.com',
                'rating' => 3,
                'comment' => '内容は参考になりましたが、一度では理解しきれない部分もありました。',
            ],

            // 坊っちゃん：3件
            [
                'isbn' => '9784101010021',
                'user_email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => '主人公の率直な性格とテンポのよい展開が印象的でした。',
            ],
            [
                'isbn' => '9784101010021',
                'user_email' => 'tanaka@example.com',
                'rating' => 3,
                'comment' => '昔の作品ですが、学校内の人間関係には現代にも通じる部分がありました。',
            ],
            [
                'isbn' => '9784101010021',
                'user_email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '短く読みやすく、正義感の強い主人公の行動を楽しく読めました。',
            ],

            // サピエンス全史：3件
            [
                'isbn' => '9784309226712',
                'user_email' => 'suzuki@example.com',
                'rating' => 5,
                'comment' => '人類の歴史を幅広い視点から捉え直すことができる興味深い内容でした。',
            ],
            [
                'isbn' => '9784309226712',
                'user_email' => 'tanaka@example.com',
                'rating' => 4,
                'comment' => '歴史と科学を結び付けた説明が分かりやすく、考えさせられました。',
            ],
            [
                'isbn' => '9784309226712',
                'user_email' => 'sato@example.com',
                'rating' => 4,
                'comment' => '分量は多いですが、人類の発展を大きな流れで理解できました。',
            ],

            // Clean Code：3件
            [
                'isbn' => '9784048930598',
                'user_email' => 'yamada@example.com',
                'rating' => 5,
                'comment' => '保守しやすいコードを書くための原則を詳しく学ぶことができました。',
            ],
            [
                'isbn' => '9784048930598',
                'user_email' => 'sato@example.com',
                'rating' => 4,
                'comment' => '関数やクラスの責務を小さく保つ重要性がよく分かりました。',
            ],
            [
                'isbn' => '9784048930598',
                'user_email' => 'takahashi@example.com',
                'rating' => 3,
                'comment' => '内容は難しい部分もありましたが、今後繰り返し読みたい技術書です。',
            ],

            // 嫌われる勇気：3件
            [
                'isbn' => '9784478025819',
                'user_email' => 'suzuki@example.com',
                'rating' => 5,
                'comment' => '他者の期待だけでなく、自分の課題に向き合う考え方が印象に残りました。',
            ],
            [
                'isbn' => '9784478025819',
                'user_email' => 'tanaka@example.com',
                'rating' => 4,
                'comment' => '対話形式なので読みやすく、アドラー心理学の考え方を理解できました。',
            ],
            [
                'isbn' => '9784478025819',
                'user_email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '人間関係に悩んだときの考え方として参考になる内容でした。',
            ],

            // 火花：3件
            [
                'isbn' => '9784163902302',
                'user_email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => '芸人として生きる二人の関係と、それぞれの葛藤が丁寧に描かれていました。',
            ],
            [
                'isbn' => '9784163902302',
                'user_email' => 'suzuki@example.com',
                'rating' => 3,
                'comment' => '独特な文章表現でしたが、才能と努力について考えさせられました。',
            ],
            [
                'isbn' => '9784163902302',
                'user_email' => 'sato@example.com',
                'rating' => 4,
                'comment' => '登場人物の不器用な生き方に現実味があり、印象に残る作品でした。',
            ],

            // FACTFULNESS：3件
            [
                'isbn' => '9784822289607',
                'user_email' => 'tanaka@example.com',
                'rating' => 5,
                'comment' => '思い込みではなく、データを確認して判断することの重要性を学べました。',
            ],
            [
                'isbn' => '9784822289607',
                'user_email' => 'sato@example.com',
                'rating' => 5,
                'comment' => '世界に対する自分の認識が、実際の統計とは異なることに驚きました。',
            ],
            [
                'isbn' => '9784822289607',
                'user_email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '図や具体例が多く、データの見方を楽しく学ぶことができました。',
            ],

            // コンテナ物語：2件
            [
                'isbn' => '9784822251468',
                'user_email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => 'コンテナの普及が物流や世界経済を大きく変えた過程が興味深かったです。',
            ],
            [
                'isbn' => '9784822251468',
                'user_email' => 'suzuki@example.com',
                'rating' => 3,
                'comment' => '専門的な部分もありましたが、物流の歴史を知ることができました。',
            ],
        ];

        foreach ($reviews as $reviewData) {
            Review::create([
                'book_id' => $bookIds->get($reviewData['isbn']),
                'user_id' => $userIds->get($reviewData['user_email']),
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'],
            ]);
        }
    }
}