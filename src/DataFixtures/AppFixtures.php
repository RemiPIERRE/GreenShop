<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    )
    {
    }

    /**
     * Catalogue réel. Ordre des colonnes :
     * [nom, prix, description, stock, jours avant aujourd'hui, actif, catégorie, image|null]
     **/
    private const PLANTS = [
        ['Monstera deliciosa', 34.90, "La plante à trous. Pousse vite, pardonne les oublis d'arrosage, réclame de la lumière indirecte et de la place.", 18, 43, true, "Tropicale", 'monstera-deliciosa-6a5dfee1572a9.jpg'],
        ['Pothos doré', 12.90, "Increvable. Supporte la pénombre, les courants d'air et les débutants. La plante à offrir en premier.", 42, 41, true, "Intérieur", 'pothos-dore-6a5e000a4b5e3.jpg'],
        ['Ficus lyrata', 49.90, "Le figuier lyre. Magnifique et susceptible : il déteste être déplacé et le fait savoir en perdant ses feuilles.", 7, 40, true, "Intérieur", 'ficus-lyrata-6a5dffff206ad.jpg'],
        ['Ficus elastica', 29.90, "Le caoutchouc. Feuillage épais et vernissé, croissance régulière, tolère un arrosage approximatif.", 15, 37, true, "Intérieur", 'ficus-elastica-6a5dfff4e6bee.jpg'],
        ['Zamioculcas zamiifolia', 27.50, "Le ZZ. Stocke l'eau dans ses rhizomes et survit à trois semaines d'absence. Idéal bureau sans fenêtre.", 24, 35, true, "Intérieur", 'zamioculcas-zamiifolia-6a5dffe95d0e3.jpg'],
        ['Sansevieria trifasciata', 19.90, "La langue de belle-mère. Verticale, graphique, quasi indestructible. Arrosez-la peu, vraiment peu.", 31, 34, true, "Intérieur", 'sansevieria-trifasciata-6a5dffddc97ee.jpg'],
        ['Calathea orbifolia', 32.00, "Feuilles rayées argent. Exigeante en humidité : elle veut une salle de bain ou un humidificateur, pas un radiateur.", 9, 32, true, "Tropicale", 'calathea-orbifolia-6a5dffd1b53ae.jpg'],
        ['Spathiphyllum wallisii', 16.90, "Le lys de la paix. Vous prévient quand elle a soif en s'affaissant, puis repart en deux heures.", 27, 30, true, "Fleurie", 'spathiphyllum-wallisii-6a5dffc735d83.webp'],
        ['Chlorophytum comosum', 9.90, "La plante araignée. Fait des bébés au bout de ses tiges, que vous pourrez replanter ou offrir.", 38, 29, true, "Intérieur", 'chlorophytum-comosum-6a5dffba87b5a.jpg'],
        ['Aloe vera', 11.50, "Utile autant que décorative. Plein soleil, terreau drainant, arrosage rare. Ne la noyez pas.", 29, 27, true, "Succulente", 'aloe-vera-6a5dffafdb091.jpg'],
        ['Echeveria elegans', 7.90, "Petite rosette bleutée. Rebord de fenêtre plein sud, un verre d'eau toutes les deux semaines l'été.", 45, 26, true, "Succulente", 'echeveria-elegans-6a5dffa52c356.jpg'],
        ['Haworthia fasciata', 8.50, "Zébrée de blanc, taille de poing. Tolère une lumière moins franche que la plupart des succulentes.", 33, 25, true, "Succulente", 'haworthia-fasciata-6a5dff8f3d5f4.jpg'],
        ['Crassula ovata', 14.90, "L'arbre de jade. Grossit lentement mais vit des décennies. Se bouture d'une simple feuille tombée.", 21, 23, true, "Succulente", 'crassula-ovata-6a5dff838f478.jpg'],
        ['Nephrolepis exaltata', 17.50, "Fougère de Boston. Veut de l'humidité constante et une lumière tamisée. Parfaite en suspension.", 13, 22, true, "Intérieur", 'nephrolepis-exaltata-6a5dff775cd18.jpg'],
        ['Asplenium nidus', 22.90, "Fougère nid d'oiseau. Feuilles ondulées en rosette. Ne mouillez jamais le cœur de la plante.", 11, 20, true, "Intérieur", 'asplenium-nidus-6a5dff68f3f6b.jpg'],
        ['Chamaedorea elegans', 24.90, "Palmier nain d'intérieur. Discret, lent, supporte les coins peu lumineux mieux que les autres palmiers.", 16, 19, true, "Tropicale", 'chamaedorea-elegans-6a5dff5e1474a.jpg'],
        ['Dypsis lutescens', 39.90, "Palmier areca. Volume immédiat dans une pièce. Beaucoup de lumière, jamais d'eau stagnante.", 8, 18, true, "Intérieur", 'dypsis-lutescens-6a5dff5361747.webp'],
        ['Strelitzia nicolai', 59.90, "L'oiseau de paradis blanc. Grande, architecturale, chère à faire pousser. Plein soleil obligatoire.", 4, 16, true, "Tropicale", 'strelitzia-nicolai-6a5dff49265ed.webp'],
        ['Alocasia zebrina', 44.00, "Tiges tigrées, feuilles en flèche. Spectaculaire et capricieuse : lumière vive, humidité, patience.", 6, 15, true, "Tropicale", null],
        ['Philodendron scandens', 15.90, "Cousin facile du Monstera. Grimpe ou retombe, au choix. Croissance rapide, entretien minimal.", 26, 14, true, "Tropicale", 'philodendron-scandens-6a5dff2e6f017.webp'],
        ['Epipremnum Marble Queen', 18.90, "Pothos panaché crème. Plus il a de lumière, plus la panachure est marquée.", 19, 12, true, "Intérieur", 'epipremnum-marble-queen-6a5dff227676a.webp'],
        ['Phalaenopsis', 21.90, "L'orchidée d'intérieur. Refleurit chaque année si on résiste à l'envie de l'arroser trop.", 23, 10, true, "Fleurie", 'phalaenopsis-6a5dff16d4655.webp'],
        ['Hoya carnosa', 23.50, "Fleur de porcelaine. Feuillage cireux, floraison parfumée après quelques années. Ne coupez pas les tiges défleuries.", 12, 8, true, "Fleurie", 'hoya-carnosa-6a5dff0be011e.webp'],
        ['Ceropegia woodii', 13.90, "Chaîne des cœurs. Retombe en longs fils, se bouture à l'infini. Lumière vive, arrosage espacé.", 17, 6, true, "Succulente", 'ceropegia-woodii-6a5dff005d24b.webp'],
        ['Dionaea muscipula', 16.50, "Attrape-mouche. Eau de pluie uniquement, jamais d'eau du robinet. Repos hivernal obligatoire.", 0, 64, false, "Intérieur", 'dionaea-muscipula-6a5e002125b2a.jpg'],
        ['Adenium obesum', 36.90, "Rose du désert. Caudex renflé, floraison rouge. Hivernage au sec, sinon elle pourrit.", 0, 56, false, "Succulente", null],
    ];

    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();

        // --- Catégories ---
        $categories = [];

        foreach (['Intérieur', 'Tropicale', 'Succulente', 'Fleurie', 'Extérieur'] as $nom) {
            $category = new Category();
            $category->setName($nom);
            $category->setSlug($slugger->slug($nom)->lower());

            $manager->persist($category);
            $categories[$nom] = $category;
        }

        // --- Produits ---
        $products = [];

        foreach (self::PLANTS as [$name, $price, $description, $stock, $daysAgo, $active, $categoryName, $image]) {
            $product = new Product();
            $product->setName($name);
            $product->setPrice((string)$price);
            $product->setDescription($description);
            $product->setStock($stock);
            $product->setCreatedAt(new \DateTimeImmutable("-$daysAgo days"));
            $product->setActive($active);
            $product->setSlug($slugger->slug($name)->lower());
            $product->setCategory($categories[$categoryName]);
            $product->setImage($image);
            if ($product->isActive())
                $products[] = $product;

            $manager->persist($product);
        }

        // --- Comptes utilisateurs ---
        $admin = new User();
        $admin->setEmail('admin@greenshop.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin1234'));
        $manager->persist($admin);

        $users = [];
        foreach (['alice', 'bob', 'chloe'] as $name) {
            $user = new User();
            $user->setEmail($name . '@example.com');
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $manager->persist($user);
            $users[] = $user;
        }

        // --- Commandes ---
        $statuses = OrderStatus::cases();
        $reference = 1;

        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $order = new Order();
                $order->setUser($user);
                $order->setReference(sprintf('CMD-2026-%04d', $reference++));
                $order->setStatus($statuses[array_rand($statuses)]);
                $order->setCreatedAt(new \DateTimeImmutable('-' . random_int(1, 90) . ' days'));

                $lineCount = random_int(1, 3);

                for ($j = 0; $j < $lineCount; $j++) {
                    $product = $products[array_rand($products)];

                    $item = new OrderItem();
                    $item->setProduct($product);
                    $item->setQuantity(random_int(1, 3));
                    $item->setUnitPrice($product->getPrice());

                    $order->addItem($item);
                    $manager->persist($item);
                }

                $order->setTotal($order->getComputedTotal());

                $manager->persist($order);
            }
        }

        $manager->flush();
    }
}
