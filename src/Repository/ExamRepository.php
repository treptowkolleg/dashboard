<?php
/*
 * Copyright (c) 2022. Benjamin Wagner
 */

namespace App\Repository;

use Core\Model\RepositoryFactory\AbstractRepositoryFactory;
use PDOException;

class ExamRepository extends AbstractRepositoryFactory
{


    /**
     * @return array|false
     */
    public function joinSchoolSubjects(int $id,string $entity)
    {
        try {
            $result = self::select
            ("
                SELECT e.id, e.is_main_school_subject AS isMainSchoolSubject, s.label, s.abbr
                FROM school_subject s 
                    INNER JOIN exam_has_school_subject e ON (e.school_subject_id = s.id)
                WHERE e.exam_id = {$id}
                    ");
            return $result->fetchAll(self::FETCH_CLASS, $entity);
        } catch (PDOException $exception) {
            return $exception->getMessage();
        }
    }

}
