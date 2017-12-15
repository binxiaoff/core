<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class AnnouncementDetails
{
    const PEJORATIVE_EVENT_CODE = [
        1221, 2141, 2151, 2153, 3100, 3101, 3102, 3109, 3210, 3211, 3212, 3220, 3232, 5000, 5001, 5002,
        5003, 5004, 5005, 5006, 5007, 5008, 5009, 5010, 5011, 5012, 5013, 5014, 5015, 5016, 5017, 5018,
        5019, 5020, 5021, 5022, 5023, 5024, 5025, 5026, 5027, 5028, 5029, 5030, 5031, 5032, 5033, 5034,
        5035, 5036, 5037, 5038, 5039, 5040, 5110, 5111, 5120, 5121, 5125, 5126, 5130, 5131, 5132, 5210,
        5211, 5212, 5213, 5220, 5221, 5222, 5223, 5224, 5225, 5226, 5227, 5228, 5229, 5230, 5231, 5232,
        5233, 5234, 5235, 5236, 5237, 5299, 5300, 5310, 5320, 5321, 5325, 5330, 5340, 5345, 5350, 5360,
        5370, 5380, 5381, 5382, 5390, 5391, 5392, 5393, 5399, 5410, 5420, 5421, 5430, 5431, 5440, 5450,
        5510, 5520, 5530, 5540, 5550, 5551, 5910, 5911, 5912, 5913, 5914, 5915, 5916, 5917, 6111, 6210,
        6220, 6240, 6241, 6300, 6313, 6320, 6321, 6322, 6323, 6330, 6331, 6332, 6333, 6334, 6335, 6336,
        6337, 6338, 6339, 6340, 6341, 6342, 6343, 6344, 6345, 6346, 6347, 6348, 6349, 6350, 6351, 6352,
        6353, 6354, 6355, 6356, 6357, 6358, 6360, 6370, 6371, 6372, 6373, 6400, 6401, 6402, 6403, 6404,
        6405, 6406, 6407, 6410, 6411, 6412, 6413, 6414, 6415, 6416, 6417, 6420, 6421, 6425, 6426, 6430,
        6431, 6432, 6433, 6434, 6435, 6436, 6437, 6438, 6441, 6442, 6443, 6444, 6445, 6446, 6447, 6450,
        6451, 6452, 6460, 6461, 6462, 6463, 6464, 6465, 6466, 6467, 6468, 6469, 6470, 6472, 6473, 6474,
        6475, 6476, 6477, 6478, 6479, 6480, 6481, 6482, 6483, 6485, 6488, 6489, 6490, 6491, 6492, 6493,
        6495, 6496, 6497, 6498, 6499, 6501, 6502, 6503, 6504, 6505, 6506, 6508, 6509, 6510, 6512, 6513,
        6514, 6900, 6901, 6902, 6903, 6904, 6905, 7121, 7130, 8410, 8411, 8430, 8440
    ];

    /**
     * @var string
     *
     * @JMS\SerializedName("annonceInfo/adID")
     * @JMS\Type("string")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @JMS\SerializedName("annonceInfo/dateParution")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $publishedDate;

    /**
     * @var AnnouncementEvent[]
     *
     * @JMS\SerializedName("evenements")
     * @JMS\XmlList(entry = "evenement")
     * @JMS\Type("ArrayCollection<Unilend\Bundle\WSClientBundle\Entity\Infolegale\AnnouncementEvent>")
     */
    private $announcementEvents;

    /**
     * @var ContentiousParticipant[]
     *
     * @JMS\SerializedName("acteursContentieux")
     * @JMS\XmlList(entry = "acteurContentieux")
     * @JMS\Type("ArrayCollection<Unilend\Bundle\WSClientBundle\Entity\Infolegale\ContentiousParticipant>")
     */
    private $contentiousParticipants;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getPublishedDate()
    {
        return $this->publishedDate;
    }

    /**
     * @return AnnouncementEvent[]
     */
    public function getAnnouncementEvents()
    {
        return $this->announcementEvents;
    }

    /**
     * @return ContentiousParticipant[]
     */
    public function getContentiousParticipants()
    {
        return $this->contentiousParticipants;
    }
}
