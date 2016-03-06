<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/Photos")
 */
class PhotosController extends Controller {

    /**
     * Zwraca liste folderów w katalogu /Data
     * @Route("/T/GetPhotos", name="photos")
     */
    public function indexAction() {
        $path = "./Data";
        $files = $this->getFolder($path, 0);
        return $this->render('default/folders.html.twig', array(
                    'files' => $files
        ));
    }

    /**
     * Zwraca liste plików z danego katalogu
     * @Route("/T/GetPhotos/{id}", name="photos_folder")
     */
    public function folderAction($id) {
        $path = "./Data/" . $id;
        $files = $this->getFolder($path, 0);
        $paths = $this->getFolder($path, 1);
        return $this->render('default/photos.html.twig', array(
                    'files' => $files,
                    'paths' => $paths,
                    'id' => $id
        ));
    }

    /**
     * Usuwa plik {photo} z katalogu .\Data\{id}
     * @Route("/T/Delete/{id}/{photo}", name="photo_delete")
     */
    public function delPhotosAction($id, $photo) {
        $path = "./Data/" . $id;
        if (is_dir($path)) {
            $dir_handle = opendir($path);
            while (($file = readdir($dir_handle)) !== false) {
                if ($file == $photo) {
                    unlink($path . "/" . $file);
                    $message = "Plik usunięty";
                    break;
                } else {
                    $message = "Plik nie istnieje";
                }
            }
            closedir($dir_handle);
        } else {
            $message = "Folder nie istnieje";
        }
        return $this->render('default/delete.html.twig', array(
                    'mess' => $message
        ));
    }

    /**
     * Pobiera i zwraca nazwy plików i folderów w folderze pod podaną nazwą
     * @param String $path ścieżka do folderu
     * @param int $option ustawienie (0 - zwróć identyfikatory, 1 - zwróć ścieżkę)
     * @return array
     */
    public function getFolder($path, $option) {
        if (is_dir($path)) {
            $dir_handle = opendir($path);
            $j = 0;
            while (($dir = readdir($dir_handle)) !== false) {
                if ($dir != "." && $dir != "..") {
                    if ($option == 0) {
                        $dirs[$j] = $dir;
                    } else if ($option == 1) {
                        $dirs[$j] = $path . "/" . $dir;
                    }
                    $j++;
                }
            }
            closedir($dir_handle);
        }
        if ($j == 0) {
            return NULL;
        }
        return $dirs;
    }

    /**
     * Pozwala na upload zdjęć do wybranego katalogu
     * @Route("/T/Upload", name="upload")
     */
    public function uploadAction(Request $Request) {
        $dirs = $this->getFolder('./Data', 0);

        $form = $this->createFormBuilder()
                ->add('folder', 'choice', array(
                    'label' => 'Wybierz folder',
                    'attr' => array(
                        'class' => 'form-control'
                    ),
                    'choices' => $dirs
                ))
                ->add('name', 'text', array(
                    'label' => 'Nazwa zdjęcia',
                    'attr' => array(
                        'class' => 'form-control'
                    )
                ))
                ->add('file', 'file', array(
                    'label' => 'Wybierz Plik',
                ))
                ->add('save', 'submit', array(
                    'label' => 'Wyślij',
                    'attr' => array(
                        'class' => 'form-control'
                    )
                ))
                ->getForm();

        $form->handleRequest($Request);

        if ($form->isValid()) {
            $savePath = $this->get('kernel')->getRootDir() . '/../web/Data/' . $dirs[$form->get('folder')->getData()];
            $formData = $form->getData();
            unset($formData['file']);
            $name = $form->get('name')->getData();
            $file = $form->get('file')->getData();
            if (is_file($dirs[$form->get('folder')->getData()] . sprintf('%s.%s', $name, $file->guessExtension()))) {
                return $this->render('default/upload.html.twig', array(
                            'form' => $form->createView(),
                            'message' => "Plik istnieje. Zmień nazwe"
                ));
            }
            if ($file !== NULL) {
                $newName = sprintf('%s.%s', $name, $file->guessExtension());
                $file->move($savePath, $newName);
            }
            $mess = "Dodano plik " . $newName . " do folderu " . $savePath;
            return $this->render('default/upload.html.twig', array(
                        'form' => $form->createView(),
                        'message' => $mess
            ));
        }


        return $this->render('default/upload.html.twig', array(
                    'form' => $form->createView()
        ));
    }

    /**
     * Zwraca liste katalogów w postaci dokumentu JSON
     * @Route("/GetPhotos", name="j_photos")
     */
    public function indexJsonAction() {
        $path = "./Data";
        $files = $this->getFolder($path, 0);
        header('Content-type: application/json');
        $json = json_encode($files);
        return $this->render('default/json.html.twig', array(
                    'json' => $json
        ));
    }

    /**
     * Zwraca liste plików z dajego katalogu w postaci dokumentu JSON
     * @Route("/GetPhotos/{id}", name="j_photos_folder")
     */
    public function folderJsonAction($id) {
        $path = "./Data/" . $id;
        $paths = $this->getFolder($path, 1);
        header('Content-type: application/json');
        $json = json_encode($paths);
        return $this->render('default/json.html.twig', array(
                    'json' => $json
        ));
    }

    /**
     * Usuwa plik {photo} z katalogu .\Data\{id}
     * Wynik przeprowadzonej operacji zwraca w postaci dokumentu JSON
     * @Route("/Delete/{id}/{photo}", name="J_photo_delete")
     */
    public function delPhotosJsonAction($id, $photo) {
        $path = "./Data/" . $id;
        $message = "Plik nie istnieje";
        if (is_dir($path)) {
            $dir_handle = opendir($path);
            while (($file = readdir($dir_handle)) !== false) {
                if ($file == $photo) {
                    unlink($path . "/" . $file);
                    $message = "Plik usunięty";
                    break;
                }
            }
            closedir($dir_handle);
        } else {
            $message = "Folder nie istnieje";
        }
        header('Content-type: application/json');
        $json = json_encode($message);
        return $this->render('default/json.html.twig', array(
                    'json' => $json
        ));
    }

}
