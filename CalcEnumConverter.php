<?php


namespace Stanford\CalcEnumConverter;

define("CALC_FIELD", "calc-field");
define("ENUM_FIELD", "enum-field");

/**
 * Class CalcEnumConverter
 * @package Stanford\CalcEnumConverter
 * @property array $instances;
 * @property int $projectId
 * @property string $instrument
 * @property \Project $project
 * @property array $fields;
 */
class CalcEnumConverter extends \ExternalModules\AbstractExternalModule
{

    private $projectId;

    private $instances;

    private $instrument;

    private $project;

    private $fields;

    public function __construct()
    {
        try {
            parent::__construct();

            if (isset($_GET['pid'])) {
                $this->setProjectId(filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT));

                $this->setInstances();
            }
        } catch (\LogicException $exception) {
            $exception->getMessage();
        }
    }

    /**
     * @return string
     */
    public function getInstrument()
    {
        return $this->instrument;
    }

    /**
     * @param string $instrument
     */
    public function setInstrument($instrument)
    {
        $this->instrument = $instrument;
    }

    /**
     * @return array
     */
    public function getInstances()
    {
        return $this->instances;
    }

    /**
     *
     */
    public function setInstances()
    {
        $this->instances = $this->getSubSettings('instance', $this->getProjectId());;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * @return \Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param \Project $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     *
     */
    public function setFields()
    {
        $this->fields = \REDCap::getFieldNames($this->getInstrument());
    }


    public function redcap_save_record(
        $project_id,
        $record = null,
        $instrument,
        $event_id,
        $group_id = null,
        $survey_hash = null,
        $response_id = null,
        $repeat_instance = 1
    ) {

        global $Proj;

        $data = array(
            \REDCap::getRecordIdField() => $record,
            'redcap_event_name' => \REDCap::getEventNames(true, true, $event_id)
        );

        $this->setProject($Proj);

        $this->setProjectId($project_id);

        $this->setInstrument($instrument);

        $this->setInstances();

        $this->setFields();

        foreach ($this->getInstances() as $instance) {
            if (in_array($instance[CALC_FIELD], $this->getFields()) && in_array($instance[ENUM_FIELD],
                    $this->getFields())) {
                $calcValue = filter_var($_POST[$instance[CALC_FIELD]], FILTER_SANITIZE_STRING);
                if ($this->isCalcValueExistInEnumList($instance[ENUM_FIELD], $calcValue)) {
                    $data[$instance[ENUM_FIELD]] = $calcValue;
                } else {
                    $data[$instance[ENUM_FIELD]] = -1;
                }
                $result = \REDCap::saveData('json', json_encode(array($data)));
                if (!empty($result['errors'])) {
                    print_r($result['errors']);
                }
            }
        }
    }

    private function isCalcValueExistInEnumList($enumName, $value)
    {
        $list = $this->getProject()->metadata[$enumName]['element_enum'];
        $list = array_keys(parseEnum($list));
        if (in_array($value, $list)) {
            return true;
        }
        return false;
    }

}