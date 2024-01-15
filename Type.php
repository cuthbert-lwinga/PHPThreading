<?PHP
namespace NameSpaceThreadActionType;

abstract class Type {
    
    /* dropped state */
    const dropped = -3;
    /* State is not set or unknown */
    const NotSet = -2;
    /* Event::$object (Future) raised error */
    const Error = -1;
    /* Event::$object was read into Event::$value */
    const Read = 0;
    /* Input for Event::$source written to Event::$object */
    const Write = 1;
    /* Event::$object (Channel) was closed */
    const Close = 2;
    /* Event::$object (Future) was cancelled */
    const Cancel = 3;
    /* Runtime executing Event::$object (Future) was killed */
    const Kill = 4;
    /* Event is in an idle state, waiting for some action */
    const Idle = 5;
    /* Event is active and in progress */
    const Active = 6;
    /* Event is in the process of loading data */
    const Loading = 7;
    
}


?>