<?php

namespace App\Controller\Profile;

use App\Entity\Exam;
use App\Entity\ExamHasExamStatus;
use App\Entity\ExamHasSchoolSubject;
use App\Entity\ExamStatus;
use App\Entity\SchoolSubject;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\UserHasExam;
use App\Menu\MenuBuilder;
use App\Repository\UserRoleRepository;
use Core\Component\DataStorageComponent\EntityManager;
use Core\Component\MenuComponent\AbstractMenu;
use Core\Controller\AbstractController;
use Core\Model\RepositoryFactory\AbstractRepositoryFactory;
use PDOException;

class KeyQuestionWorkflowController extends AbstractController
{

    protected AbstractRepositoryFactory $repository;

    /**
     * @var array|false|string
     */
    private $schoolSubjects;
    private AbstractMenu $adminMenu;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new UserRoleRepository();
        $mainMenu = new MenuBuilder();
        $mainMenu->createMenu();
        $this->schoolSubjects = $this->getRepositoryManager()->findAll(SchoolSubject::class,['label' => 'asc']);
        $this->getView()->addData([
            'schoolSubjects' => $this->schoolSubjects,
            'mainMenu' => $mainMenu->render(),
            'repository' => $this->repository
        ]);

        $this->denyAccessUnlessHasPermission('create_key_question');
    }

    public function index(): string
    {
        return $this->render('approval_process/index.html',[

        ]);
    }

    /**
     * @param int $examId
     * @return string|void
     */
    public function claimKeyQuestion(int $examId)
    {
        $exam = $this->repository->find(Exam::class,$examId);
        if(date('Y') >= ($exam->getYear()+3) and !$exam->getUser() and $this->session->getUser())
            {
                return $this->render('approval_process/claim_start_form.html',[
                    'exam' => $exam,
                ]);
            }
        $this->setFlash('key_question_locked','danger');
        $this->response->redirectToRoute(302,$this->generateUrlFromRoute('exam_show',[$exam->getId()]),true);
    }

    public function transferKeyQuestion()
    {
        $exam = $this->repository->find(Exam::class,$this->request->getFieldAsString('exam_id'));

        if($this->request->isFormSubmitted() and $this->request->isPostRequest() and !$exam->getUser())
        {
            $user = $this->repository->findOneBy(User::class,['username' => $this->request->getFieldAsString('username')]);
            $topic = $this->repository->findOneBy(Topic::class,['title' => $this->request->getFieldAsString('topic')]);
            $mainSubject = $this->repository->findOneBy(SchoolSubject::class,['label' => $this->request->getFieldAsString('school_subject_1')]);
            $secondarySubject = $this->repository->findOneBy(SchoolSubject::class,['label' => $this->request->getFieldAsString('school_subject_2')]);

            $userHasExam = new UserHasExam();
            $entityManager = new EntityManager();

            $userHasExam->setUserId($user->getId());
            $userHasExam->setKeyQuestion($this->request->getFieldAsString('key_question'));
            $userHasExam->setTopicId($topic->getId());
            $userHasExam->setMainSubjectId($mainSubject->getId());
            $userHasExam->setSecondarySubjectId($secondarySubject->getId());


            $userExamId = $entityManager->persist($userHasExam);

            $status = $this->repository->findOneBy(ExamStatus::class,['label' => 'clearance']);

            $examStatus = new ExamHasExamStatus();

            $examStatus->setInfo("Antrag angelegt");
            $examStatus->setUserExamId($userExamId);
            $examStatus->setSupervisorId($user->getId());
            $examStatus->setExamStatusId($status->getId());

            $entityManager->persist($examStatus);

            $exams = $this->repository->findBy(ExamHasSchoolSubject::class,['exam_id' => $this->request->getFieldAsString('exam_id')]);

            foreach ($exams as $examObject)
            {
                $examObject->setUserId($user->getid());

                $entityManager->persist($examObject,$examObject->getId());
            }

        }
        $this->setFlash('key_question_send_to_clearance');
        $this->response->redirectToRoute(302,'user_profile_index');
    }

}
