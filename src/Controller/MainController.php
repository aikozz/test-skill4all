<?php

namespace App\Controller;


use App\Entity\Car;
use App\Repository\CarRepository;
use App\Entity\CarCategory;
use App\Repository\CarCategoryRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;


class MainController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(CarCategoryRepository $carCatRepository): Response
    {
        $carCat = $carCatRepository->findAll();
       
      
        $weather = json_decode(file_get_contents('https://api.open-meteo.com/v1/forecast?latitude=48.8567&longitude=2.3510&current_weather=true')); 
        $form = $this->createFormBuilder()
                ->setAction($this->generateUrl('search', array('slug' => 'search')))
                ->add('nom', TextType::class)
                ->add('Rechercher', SubmitType::class)
                ->getForm();
        return $this->renderForm('test/index.html.twig', [
            'weather' => $weather->current_weather->temperature,
            'form' => $form,
            'carCat' => $carCat
        ]);
    }

    #[Route('/rechercher', name: 'search')]
    public function search(CarRepository $carRepository , PaginatorInterface $paginator,  Request $request): Response
    {
        $name = $request->request->all();

        if(isset($name['form']['nom'])){
            $nom = $name['form']['nom'];
        }
        else{
            $nom = $request->query->get('nom');
        }
       
        $carsQuery = $carRepository->createQueryBuilder('car')
                ->where('car.name LIKE :name')
                ->setParameter('name', $nom .'%')
                ->getQuery();
                
                $pagination = $paginator->paginate(
                    $carsQuery, /* query NOT result */
                    $request->query->getInt('page', 1), /*page number*/
                    20 /*limit per page*/, 
                );
                $pagination->setParam('nom', $nom);
        $cars = $carsQuery->execute();
        return $this->render('test/resultat.html.twig',[
            'cars' => $cars,
            'pagination' => $pagination,
            'admin' => false
        ]);    
    }

    #[Route('/admin', name: 'admin')]
    public function admin(CarRepository $carRepository , PaginatorInterface $paginator,  Request $request): Response
    {
        $carsQuery = $carRepository->createQueryBuilder('car')
                ->getQuery();
                
                $pagination = $paginator->paginate(
                    $carsQuery, /* query NOT result */
                    $request->query->getInt('page', 1), /*page number*/
                    20 /*limit per page*/, 
                );
              
        $cars = $carsQuery->execute();
        return $this->render('test/resultat.html.twig',[
            'cars' => $cars,
            'pagination' => $pagination,
            'admin' => true
        ]);    
    }


    #[Route('/rechercher_by_cat', name: 'search_cat')]
    public function searchByCategory(CarRepository $carRepository, PaginatorInterface $paginator,  Request $request): Response
    {
        $idCat = $request->query->get('idCat');
        $carsQuery = $carRepository->createQueryBuilder('car')
                ->where('car.category = :id')
                ->setParameter('id', $idCat)
                ->getQuery();
                $pagination = $paginator->paginate(
                    $carsQuery, /* query NOT result */
                    $request->query->getInt('page', 1), /*page number*/
                    20 /*limit per page*/, 
                );
                $pagination->setParam('idCat', $idCat);
        $cars = $carsQuery->execute();
      
        return $this->render('test/resultat.html.twig',[
            'cars' => $cars,
            'pagination' => $pagination,
            'admin' => false
        ]);    
    }


    #[Route('/delete', name: 'delete')]
    public function deleteCar(CarRepository $carRepository,EntityManagerInterface $manager, Request $request): Response
    {
        $idCar = $request->query->get('idCar');
        $car = $carRepository->find($idCar);
        if (!$car) {
            throw $this->createNotFoundException(
                'No car found for id '.$idCar
            );
        }

      
        $manager->remove($car);
        $manager->flush();
        return $this->redirectToRoute('admin',);
    }


    #[Route('/update_or_create', name: 'update_or_create')]
    public function update_or_create(CarCategoryRepository $carCatRepository, CarRepository $carRepository, Request $request): Response
    {
        $idCar = $request->query->get('idCar');
        $carCat = $carCatRepository->findAll();
        if(!empty($idCar)){
            $car = $carRepository->find($idCar);
            $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('update', array('slug' => 'update')))
            ->add('Id', IntegerType::class, ['data' => $car->getId()] )
            ->add('nom', TextType::class, ['data' => $car->getName()] )
            ->add('Nb_porte', IntegerType::class, ['data' => $car->getNbDoors()] )
            ->add('Nb_siege', IntegerType::class, ['data' => $car->getNbSeats()] )
            ->add('Prix', NumberType::class, ['data' => $car->getCost()] )
            ->add('Category', ChoiceType::class, [ 'choices' => $carCat ], ['data' => $car->getCategory()] )
            ->add('Modifier', SubmitType::class)
            ->getForm();
        }else{
       
            $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('create', array('slug' => 'create')))
            ->add('nom', TextType::class )
            ->add('Nb_porte', IntegerType::class )
            ->add('Nb_siege', IntegerType::class )
            ->add('Prix', NumberType::class )
            ->add('Category', ChoiceType::class, [ 'choices' => $carCat ] )
            ->add('Modifier', SubmitType::class)
            ->getForm();
        }
       
    
        return $this->renderForm('test/create_or_update.html.twig',[
            'form' => $form
        ]);
    }

    
    #[Route('/update', name: 'update')]
    public function updateCar(CarRepository $carRepository, CarCategoryRepository $carCatRepository, EntityManagerInterface $manager, Request $request): Response
    {
        $result = $request->request->all();       
        $car = $carRepository->find($result['form']['Id']);
        if (!$car) {
            throw $this->createNotFoundException(
                'No car found for id '.$idCar
            );
        }
        $category = $carCatRepository->find($result['form']['Category']);
        $car->setName($result['form']['nom']);
        $car->setCost($result['form']['Prix']);
        $car->setNbSeats($result['form']['Nb_siege']);
        $car->setNbDoors($result['form']['Nb_porte']);
        $car->setCategory($category);
        $manager->flush();
        return $this->redirectToRoute('admin',);
    }

    #[Route('/create', name: 'create')]
    public function createCar(CarRepository $carRepository, CarCategoryRepository $carCatRepository, EntityManagerInterface $manager, Request $request): Response
    {

        $result = $request->request->all();   
        //$category = ; 
        var_dump($carCatRepository->find($result['form']['Category']), $result['form']['Category']);  
        $car = new Car();
        $car->setName($result['form']['nom']);
        $car->setCost($result['form']['Prix']);
        $car->setNbSeats($result['form']['Nb_siege']);
        $car->setNbDoors($result['form']['Nb_porte']);
        $car->setCategory($category);
        $manager->persist($car);
        $manager->flush();
        return $this->redirectToRoute('admin',);
    }


    


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'    => 'App\Entity\Car',
            'pagination'    => null    
        ));
    }
}
