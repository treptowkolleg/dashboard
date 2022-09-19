<?php
/*
 * Copyright (c) 2022. Benjamin Wagner
 */

/**
 * @var object $response enthält Response-Daten des Controllers
 * @var object $schoolSubjects enthält die MySQL-Tabelle "school_subject"
 * @var int $current_school_subject_id Id des aktuellen Schulfachs
 * @var object $examsByMainSchoolSubject enthält die MySQL-Tabelle "exam"
 * @var object $examsBySecondarySchoolSubject enthält die MySQL-Tabelle "exam"
 * @var object $currentSchoolSubject enthält aktuelles Schulfach
 */

use Core\Component\SessionComponent\Session;

/**
 * Übergeordnetes Template
 */
$this->layout('_layout.standard.html',
    [
        'current_school_subject_id' => $current_school_subject_id
    ]
);

?>

<?php $this->start('main') ?>
    <div class="row g-3 mb-3">

        <div class="col-12">
            <div class="d-block d-md-none">
                <div class="h6 fw-light text-muted text-uppercase mb-2 ps-3">Aktuelles Fach</div>
                <div class="border rounded-3 bg-white lh-sm p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-light text-primary text-capitalize me-1" style="width: 40px"><?=$currentSchoolSubject->getAbbr() ?></span>
                        <strong><?=$currentSchoolSubject->getLabel();?></strong>
                    </div>
                    <span class="badge text-bg-light text-muted"><?=$currentSchoolSubject->countExams()?></span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="d-none d-md-block">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="h6 fw-light text-muted text-uppercase mb-0 ps-3">Fächer</div>
                    <a class="btn btn-sm btn-light border d-inline-block d-md-none" data-bs-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
                        Fächer ein-/ausblenden
                    </a>
                </div>

                <div class="collapse show" id="collapseExample">
                    <div class="list-group list-group-flush rounded-3 border">
                        <?php foreach($schoolSubjects as $subject): ?>
                            <?php $isActive = ($current_school_subject_id == $subject->getId()) ? 'active' : '';?>
                            <a href="<?=$response->generateUrlFromRoute('exam_list',[$subject->getId()]) ?>" class="list-group-item <?=$isActive?> list-group-item-action lh-sm py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-light text-primary text-capitalize me-1" style="width: 40px"><?=$subject->getAbbr() ?></span>
                                    <strong><?=$subject->getLabel() ?></strong>
                                </div>
                                <span class="badge text-bg-light text-muted"><?=$subject->countExams()?></span>
                            </a>
                        <?php endforeach; ?>
                        <?php if(!$schoolSubjects): ?>
                            <li class="list-group-item">Keine Prüfungen gefunden.</li>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="row g-3">
                <div class="col-12">
                    <div class="h6 fw-light text-muted text-uppercase mb-2 ps-3">Referenzfach Prüfungsthemen</div>
                    <div class="list-group list-group-flush rounded-3 border">
                        <?php foreach($examsByMainSchoolSubject as $exam): ?>
                            <a href="<?=$response->generateUrlFromRoute('exam_show',[$exam->getId()])?>" class="list-group-item list-group-item-action lh-sm py-3 d-flex justify-content-between align-items-start">
                                <div class="d-flex flex-column justify-content-between align-items-start">
                                    <strong><?=$exam->getTopic()->getTitle() ?></strong>
                                    <small class="text-truncate text-wrap"><?=$exam->getTopic()->getDescription() ?></small>

                                    <div class="d-flex my-2 small fw-light justify-content-start align-items-center">
                                        <?php foreach($exam->getSchoolSubjects() as $subject): ?>
                                            <span class="badge me-1 text-capitalize <?=$subject->isMainSchoolSubject() ? 'bg-primary' :'bg-secondary' ?>"><?=$subject->getAbbr()?></span>
                                        <?php endforeach; ?>
                                        <?php if(date('Y') < ($exam->getYear()+3)):?>
                                        <span class="me-1 badge badge-pill text-bg-danger small">gesperrt</span>
                                        <?php endif; ?>
                                        <?php if($exam->getUser()):?>
                                            <span class="badge badge-pill text-bg-info small">belegt</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text small"><?=$exam->getKeyQuestion()?></p>

                                </div>
                                <div class="d-flex align-items-end flex-column">
                                    <span class="small text-nowrap">frei ab</span>
                                    <?= $exam->getYear()+3 ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        <?php if(!$examsByMainSchoolSubject): ?>
                            <li class="list-group-item">Keine Prüfungen gefunden.</li>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="h6 fw-light text-muted text-uppercase mb-2 ps-3">Begleitfach Prüfungsthemen</div>
                    <div class="list-group list-group-flush rounded-3 border">
                        <?php foreach($examsBySecondarySchoolSubject as $exam): ?>
                            <a href="<?=$response->generateUrlFromRoute('exam_show',[$exam->getId()])?>" class="list-group-item list-group-item-action lh-sm py-3 d-flex justify-content-between align-items-start">
                                <div class="d-flex flex-column justify-content-between align-items-start">
                                    <strong><?=$exam->getTopic()->getTitle() ?></strong>
                                    <small class="text-truncate text-wrap"><?=$exam->getTopic()->getDescription() ?></small>

                                    <div class="d-flex my-2 small fw-light justify-content-start align-items-center">
                                        <?php foreach($exam->getSchoolSubjects() as $subject): ?>
                                            <span class="badge me-1 text-capitalize <?=$subject->isMainSchoolSubject() ? 'bg-primary' :'bg-secondary' ?>"><?=$subject->getAbbr()?></span>
                                        <?php endforeach; ?>
                                        <?php if(date('Y') < ($exam->getYear()+3)):?>
                                            <span class="me-1 badge badge-pill text-bg-danger small">gesperrt</span>
                                        <?php endif; ?>
                                        <?php if($exam->getUser()):?>
                                            <span class="badge badge-pill text-bg-info small">belegt</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text small"><?=$exam->getKeyQuestion()?></p>
                                </div>
                                <div class="d-flex align-items-end flex-column">
                                    <span class="small text-nowrap">frei ab</span>
                                    <?= $exam->getYear()+3 ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        <?php if(!$examsBySecondarySchoolSubject): ?>
                            <li class="list-group-item">Keine Prüfungen gefunden.</li>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $this->stop() ?>