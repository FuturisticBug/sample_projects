//
//  ELRoadViewController.m
//  EzyLog
//
//  Created by Rahul Singha Roy on 18/06/15.
//  Copyright (c) 2015 Esolz. All rights reserved.
//

#import "ELRoadViewController.h"
#import "ELDriveSummaryController.h"
#import <CoreLocation/CoreLocation.h>
#import <MapKit/MapKit.h>
#import "ELActivityLogViewController.h"
#import "UIView+Facade.h"
#import "AppDelegate.h"
#import "RS_JsonClass.h"
#import "MACircleProgressIndicator.h"
#import "ELFBShareViewController.h"
#import "Reachability.h"
#import "OWMWeatherAPI.h"
#import "AppDelegate.h"
#import "ELDriveSignatureViewController.h"

@import GoogleMaps;


@interface ELRoadViewController ()<MKMapViewDelegate,CLLocationManagerDelegate,UIGestureRecognizerDelegate,UIAlertViewDelegate,UINavigationControllerDelegate, UIDocumentInteractionControllerDelegate,GMSMapViewDelegate,UIGestureRecognizerDelegate>



{
    NSString *postData;
    NSMutableDictionary *postDict;
    
    NSString *latBck,*longBck;
    
    BOOL driveEnds,isPark,isTraffic,isSignal;
    int averageSpeed,averageSpeedOld,stopCount;
    // 10 MIN Idle drive end variables
    NSOperationQueue *opQueue;
    
    NSMutableDictionary *json;
    NSArray *hourFragment;
    AppDelegate *app;
    NSArray *timeArr;
    NSTimer *timer,*driveTimer;
    
    BOOL nodrive;
    
    NSString *prevDistance,*currentdistance;
    
    UIAlertView *alertEnd;
    
    UILongPressGestureRecognizer *endPress;
    //
    
    
    __weak IBOutlet UIButton *fbshareBtn;
    
    __weak IBOutlet UIButton *igShareBtn;
    
    NSString *encodedPath;
    
    UIImage *mapImg ;
    
    IBOutlet UIView *spinnerView;
    
    IBOutlet UIActivityIndicatorView *spinner;
    
    NSData *mapScreenData;
    
    AppDelegate *appDel ;
    
    __weak IBOutlet UIButton *circleBtn;
    
    CLLocationManager  *locationManager;
    
    IBOutlet UIView *testScreen;
    
    IBOutlet UIImageView *screenShot;
    
    IBOutlet UIImageView *backGroundView;
    
    
    IBOutlet UIImageView *mapBottomBar;
    
    
    RS_JsonClass *globalOBJ;
    
    CGRect mainFrame;
    
    BOOL _tracking;
    
    //url related parameters
    
    float deviceSpeed;
    
    
    NSString *driveDistance;
    
    NSString *driveSpeed;
    
    NSString *startTime;
    
    NSDate* dateStart;
    
    NSString *endTime;
    
    NSDate* dateEnd;
    
    NSString *totalHours;
    
    NSString *dayHours;
    
    NSString *nightHours;
    
    NSString *driverID;
    
    NSString *supID;
    
    NSString *carID;
    
    float startLat;
    
    float startLong;
    
    float endLat;
    
    float endLong;
    
    BOOL islicensed;
    
    NSString *temperature;
    
    int dayBonusTime,nightBonusTime;
    
    
    //Strings to store which button pressed
    
    NSString *parkingStrng,*trafficStrng,*roadStrng;
    
    //Drive ID after end drive...
    
    NSString *driveID;
    
    
    NSArray *total,*day,*night;
    
    int t,d,n;
    
    
    //timer variables
    
    IBOutlet UILabel *hourLbl;
    
    
    IBOutlet UILabel *minLbl;
    
    
    IBOutlet UILabel *secLbl;
    
    float hour,min,sec;
    
    
    IBOutlet UIButton *endDriveButton;
    
    UILongPressGestureRecognizer *longPress;
    
    BOOL driveEnded;
    
    
    NSInteger dayStart,dayEnd;
    NSInteger monthStart,monthEnd;
    NSInteger yearStart,yearEnd;
    
    
    int clockhour,lostHour;
    
    
    
    //timer time variables
    
    NSString *startTimerTime,*currentTimerTime;
    
    BOOL timerStart;
    
    NSString *secTIme,*minTime,*HourTime;
    
    float secCount;
    
    
    IBOutlet UIButton *backButton;
    
    
    CLLocationCoordinate2D target;
    
    
    NSString *sunrise,*sunset,*weatherId,*stateId,*startDate,*endDate,*driveTime;
    NSDate *startDateTime,*endDateTime,*sunriseTime,*sunsetTime;
    
    
    float driveHourTemp;
    int weatherFire,dayTimeFire;
    
    UIView *blackView;
    UILabel *warningLbl;
    
    
    
}
@property(nonatomic, strong)     UIDocumentInteractionController* docController;
//For timer..

@property (strong, nonatomic) NSTimer *stopWatchTimer; // Store the timer that fires after a certain time
@property (strong, nonatomic) NSDate *startTimerDate;

//Timer ends

@property(nonatomic,strong)UILongPressGestureRecognizer *longPress ;

@property (strong, nonatomic) IBOutlet UIButton *parkBtn1;

@property (strong, nonatomic) IBOutlet UIButton *parkBtn2;


@property (strong, nonatomic) IBOutlet UILabel *view3Lbl;

@property (strong, nonatomic) IBOutlet UILabel *view2Lbl;

@property (strong, nonatomic) IBOutlet UILabel *view1Lbl;

@property (weak, nonatomic) IBOutlet UIView *totalScreenView;

@property (weak, nonatomic) IBOutlet GMSMapView *gmapView;

@property(copy, nonatomic) NSSet *markers;

@property (weak, nonatomic) IBOutlet MKMapView *mapDrive;

//Buttons in mapsPage

@property (strong, nonatomic) IBOutlet UIButton *sealedBtn;

@property (weak, nonatomic) IBOutlet UILabel *timeLabel;

@property (weak, nonatomic) IBOutlet UILabel *dateLbl;

@property (strong, nonatomic) IBOutlet UIButton *unsealedBtn;


@property (strong, nonatomic) IBOutlet UIButton *homeBtn;


@property (strong, nonatomic) IBOutlet UIButton *busyBtn;


@property (strong, nonatomic) IBOutlet UIButton *multiLaneBtn;


@property (strong, nonatomic) IBOutlet UIButton *twoCarBtn;


@property (strong, nonatomic) IBOutlet UIButton *fourCarBtn;


@property (strong, nonatomic) IBOutlet UIButton *sixCarBtn;





@property (strong, nonatomic) IBOutlet UIView *parkingView;

@property (strong, nonatomic) IBOutlet UIView *trafficView;

@property (strong, nonatomic) IBOutlet UIView *signalView;

@property (strong, nonatomic) IBOutlet UIButton *sigbtn;

@end

@implementation ELRoadViewController

@synthesize longPress,background,mapDrive;


- (void)dealloc {
    //  [_gmapView removeObserver:self forKeyPath:@"myLocation"];
}

- (void)observeValueForKeyPath:(NSString *)keyPath ofObject:(id)object change:(NSDictionary *)change context:(void *)context {
    if([keyPath isEqualToString:@"myLocation"]) {
        CLLocation *location = [object myLocation];
        //...
        //NSLog(@"Location, %@,", location);
        
        //        GMSMarker *pointMarker = [GMSMarker markerWithPosition:CLLocationCoordinate2DMake(location.coordinate.latitude, location.coordinate.longitude)];
        //        pointMarker.icon = [UIImage imageNamed:@"YourImage"];
        //        pointMarker.map = _gmapView;
        
        
        
        
        CLLocationCoordinate2DMake(location.coordinate.latitude, location.coordinate.longitude);
        
        
        
        
        if(startLat==0 && startLong==0)
        {
            
            
            startLat=location.coordinate.latitude;
            startLong=location.coordinate.longitude;
            
            
        }
        else
        {
            endLat=location.coordinate.latitude;
            endLong=location.coordinate.longitude;
        }
        
        target = CLLocationCoordinate2DMake(location.coordinate.latitude, location.coordinate.longitude);
        
      //  latBck =[NSString stringWithFormat:@"%f", location.coordinate.latitude];
      //  longBck =[NSString stringWithFormat:@"%f", location.coordinate.longitude];
        
        [_gmapView animateToLocation:target];
        [_gmapView animateToZoom:17];
        
        
    }
    
    
    
    
}

- (void) noInternetHandler:(NSNotification *)pNotification
{
    
    NSLog(@"There IS NO internet connection");
    
    UIAlertView *networkAlert=[[UIAlertView alloc]initWithTitle:@"Message" message:@"Check your Internet Connection" delegate:self cancelButtonTitle:@"OK" otherButtonTitles: nil];
    [networkAlert show];
    
}


- (void)viewDidLoad {
    
       [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(noInternetHandler:) name:@"no_internet" object:nil];
    
    stopCount=0;
    
      appDel = (AppDelegate *)[[UIApplication sharedApplication] delegate];
    
    endPress=[[UILongPressGestureRecognizer alloc]initWithTarget:self action:@selector(End_Drive_Action)];
    endPress.minimumPressDuration=0.5;
    [endPress setNumberOfTouchesRequired:1];
    [endPress setEnabled:YES];
    [endPress setDelaysTouchesEnded:YES];
    [endPress setCancelsTouchesInView:YES];
    [endPress setNumberOfTapsRequired:0];
    [endPress setDelegate:self];
    
    

  
        [endDriveButton addGestureRecognizer:endPress];
    [endDriveButton.layer setZPosition:800];
    
      [circleBtn addGestureRecognizer:endPress];
    [endDriveButton setUserInteractionEnabled:YES];
    
    
    driveHourTemp=[[[NSUserDefaults standardUserDefaults]objectForKey:@"totalDriveHour" ] floatValue];
    
    
    
    NSLog(@"total driven minutes %f",driveHourTemp);
    
    
    [[NSUserDefaults standardUserDefaults]setBool:true forKey:@"driveOn"];
    
    
    [fbshareBtn setUserInteractionEnabled:NO];
    [igShareBtn setUserInteractionEnabled:NO];
    
    weatherFire=0;
    dayTimeFire=0;
    
    spinnerView.hidden=YES;
    
    driveEnds=NO;
    
    nodrive=NO;
    
   // prevDistance=@"0.00 KMS";
    
    timer=[NSTimer scheduledTimerWithTimeInterval:60
                                           target:self
                                         selector:@selector(targetMethod:)
                                         userInfo:nil
                                          repeats:YES];
    [timer fire];
    
    
    
    
    
    
    
    
    
    // backGroundView.image=[UIImage imageNamed:@"red_mode"];
    
    
    if([[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]<10)
    {
        backGroundView.image=[UIImage imageNamed:@"red_mode"];
    }
    
    else  if([[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]>=10 && [[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]<20)
    {
        backGroundView.image=[UIImage imageNamed:@"pink_mode"];
    }
    
    else if([[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]>=20 && [[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]<50)
    {
        backGroundView.image=[UIImage imageNamed:@"orange_mode"];
    }
    else if([[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]>=50 && [[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]<80)
    {
        backGroundView.image=[UIImage imageNamed:@"yellow_mode"];
    }
    else if([[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]>=80 && [[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]<100)
    {
        backGroundView.image=[UIImage imageNamed:@"blue_mode"];
    }
    else if([[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]>=100 && [[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]<120)
    {
        backGroundView.image=[UIImage imageNamed:@"purple_mode"];
    }
    
    else if ([[[NSUserDefaults standardUserDefaults] valueForKey:@"drivehour"] floatValue]>=120)
    {
        backGroundView.image=[UIImage imageNamed:@"green_mode"];
    }
    
    
    
    testScreen.hidden=YES;

    
    
    secCount=0;
    
    [super viewDidLoad];
    
    
    NSDateComponents *components = [[NSCalendar currentCalendar] components:NSCalendarUnitDay | NSCalendarUnitMonth | NSCalendarUnitYear fromDate:[NSDate date]];
    
    
    dayStart = [components day];
    monthStart = [components month];
    yearStart = [components year];
    
    
    //NSLog(@"Start date..... %ld/%ld/%ld",dayStart,monthStart,yearStart);
    
    
    timerStart=NO;
    
    driveEnded=NO;
    
    hour=.00;
    min=.00;
    //min=.59;
    sec=.00;
    
    total=[[NSArray alloc]init];
    day=[[NSArray alloc]init];
    night=[[NSArray alloc]init];
    
    t=d=n=0;
    
    dayHours=@"";
    nightHours=@"";
    
    app=[[UIApplication sharedApplication] delegate];
    
    driverID=app.userID;
    supID=app.superID;
    carID=app.carID;
    islicensed=app.islicensed;
    
    
    
    
    
    _gmapView.settings.compassButton = YES;
    _gmapView.myLocationEnabled = YES;
    _gmapView.mapType = kGMSTypeNormal;
    
    _gmapView.settings.myLocationButton = YES;
    
    
    
    _gmapView.delegate=self;
    
    
    
    //NSLog(@"User's location: %@", _gmapView.myLocation);
    
    //    mapDrive.delegate=self;
    //    [mapDrive setShowsUserLocation:TRUE];
    
    
    //    locationManager = [[CLLocationManager alloc] init];
    //    locationManager.delegate = self;
    //    locationManager.desiredAccuracy = kCLLocationAccuracyNearestTenMeters;
    //
    //    [locationManager requestWhenInUseAuthorization];
    //    [locationManager startMonitoringSignificantLocationChanges];
    //
    //    //    if(locationManager.locationServicesEnabled)
    //    //    {
    //    [locationManager startUpdatingLocation];
    //    //    }
    
    
    
    //  //NSLog(@"Current location....%.2f",mapDrive.userLocation.coordinate.latitude);
    
    
    //url fire related data
    
    startLat=[[NSString stringWithFormat:@"0"] floatValue];
    startLong=[[NSString stringWithFormat:@"0"] floatValue];
    
    
    mainFrame=_signalView.frame;
    
    
    _parkingView.frame=_signalView.frame;
    
    //    _trafficView.hidden=YES;
    //
    //    _signalView.hidden=YES;
    
    CGRect tempFrame=_trafficView.frame;
    
    tempFrame.origin.x=[UIScreen mainScreen].bounds.size.width;
    tempFrame.origin.y=mainFrame.origin.y;
    
    _trafficView.frame=tempFrame;
    
    _signalView.frame=tempFrame;
    
    _sigbtn.userInteractionEnabled=YES;
    
    
    
    
    
    
    // Do any additional setup after loading the view.
    self.mapview.delegate=self;
    CGRect screenBounds=[[UIScreen mainScreen] bounds];
    
    
    
    if (screenBounds.size.height == 667 && screenBounds.size.width == 375)
    {
        _quietstreetlabel.frame=CGRectMake(80, 505, 400, 35);
        [_quietstreetlabel setFont:[UIFont fontWithName:@"OpenSans" size:20] ];
    }
    
    else if (screenBounds.size.height >= 667 && screenBounds.size.width >= 375)
    {
        _quietstreetlabel.frame=CGRectMake(90, 560, 400, 35);
        [_quietstreetlabel setFont:[UIFont fontWithName:@"OpenSans" size:22] ];
    }
    
    ///////// ---------  /////////////
    
    
    
    self.view.backgroundColor = [UIColor colorWithRed:13/255.0 green:14/255.0 blue:20/255.0 alpha:1.0];
    self.edgesForExtendedLayout = UIRectEdgeNone;
    
            self.follower = [Follower new];
            self.follower.delegate = self;
    
    
            [self.follower beginRouteTracking];
    
    
            driveTimer=[NSTimer scheduledTimerWithTimeInterval:5
                                                   target:self
                                                      selector:@selector(driveCheck:)
                                                 userInfo:nil
                                                  repeats:YES];
            [driveTimer fire];
    
    
    
//    self.follower=appDel.follower;
 
//    [self.follower  resetMetrics2];
    
    self.mapDrive.delegate = self;
    self.mapDrive.showsUserLocation = YES;
    
    
    self.maskView = [UIView new];
    self.maskView.backgroundColor = [UIColor colorWithWhite:0.0 alpha:0.8];
    [_gmapView addSubview:self.maskView];
    
    
    
    
    NSDateFormatter *dateFormatterTimer = [[NSDateFormatter alloc] init];
    
    [dateFormatterTimer setDateFormat:@"HH.mm.ss"];
    
    //  startTimerTime=[dateFormatterTimer stringFromDate:[NSDate date]];
    
    //NSLog(@"Start time________>%@",startTimerTime);
    
    
    //
    
    MACircleProgressIndicator *appearance = [MACircleProgressIndicator appearance];
    
    // if([[UIScreen mainScreen] bounds].size.height==480)
    
    self.smallProgressIndicator.frame=endDriveButton.frame;
    
    appearance.color = [UIColor whiteColor];
    appearance.strokeWidth = 5.0;    //timer start
    
    timerStart=YES;
    
    
    // [self performSelectorOnMainThread:@selector(Timer_start:) withObject:nil waitUntilDone:NO];
    
    
    //  [self checkWeather];
    
    
    [self Timer_start];
    
}


-(void)driveCheck:(id)sender
{

    if( deviceSpeed>16.0)
    {
        
        
        if(![self.view.subviews containsObject:blackView])
        {
            blackView=[[UIView alloc]initWithFrame:CGRectMake(0, 0, [UIScreen mainScreen].bounds.size.width, [UIScreen mainScreen].bounds.size.height)];
            
            [blackView setBackgroundColor:[UIColor blackColor]];
            
            [blackView setAlpha:0.7];
            
            warningLbl=[[UILabel alloc]initWithFrame:CGRectMake(0, [UIScreen mainScreen].bounds.size.height/2, [UIScreen mainScreen].bounds.size.width, 20.0/320.0*[UIScreen mainScreen].bounds.size.height)];
            
            [warningLbl setText:@"Please do not use the app while driving"];
            [warningLbl setFont:[UIFont boldSystemFontOfSize:18.0]];
            [warningLbl setTextColor:[UIColor whiteColor]];
            [warningLbl setTextAlignment:NSTextAlignmentCenter];
            [warningLbl setNumberOfLines:2];
            [warningLbl setLineBreakMode:NSLineBreakByWordWrapping];
            
            
            
            
            
            
            [self.view addSubview:blackView];
            
            [self.view addSubview:warningLbl];
        }
        
        
    }
    else
    {
        
        //  pDistance=cDistance;
        
        if ([self.view.subviews containsObject:blackView]) {
            [blackView removeFromSuperview];
        }
        if ([self.view.subviews containsObject:warningLbl]) {
            [warningLbl removeFromSuperview];
        }
        
        
        
        
    }
    

    
   
    
    
}

- (BOOL)gestureRecognizerShouldBegin:(UIGestureRecognizer *)gestureRecognizer
{

    return  YES;
    
}


-(void)checkWeather
{
    
    
    OWMWeatherAPI *weatherAPI = [[OWMWeatherAPI alloc] initWithAPIKey:@"ef70aad56b044855ec8adad32ce93721"];
    [weatherAPI setTemperatureFormat:kOWMTempCelcius];
    
//    NSDateFormatter * Dateformat= [[NSDateFormatter alloc] init];
//    
//    [Dateformat setDateFormat:@"yyyy-MM-dd HH:mm:ss Z"];
//    Dateformat.timeZone = [NSTimeZone timeZoneWithAbbreviation:@"UTC"];
//    
//    
//    
//   // target.latitude=-21.15;
//   // target.longitude=149.2;
//    
////    
//  //  target.latitude=-33.87;
//   //   target.longitude=151.21;
//    
//    
//  // target.latitude=22.35;
//   // target.longitude=88.28;
//    
//   target.latitude=-37.81;
//    target.longitude=144.96;
//    
//   NSLog(@"target lat:%f target Long:%f",target.latitude,target.longitude);
    
    
    [weatherAPI currentWeatherByCoordinate:(target) withCallback:^(NSError *error, NSDictionary *result) {
        
        
       if ([[result objectForKey:@"cod"] intValue]!=404) {
            
  NSDictionary *locationStats=[result objectForKey:@"sys"];
            
          stateId=[NSString stringWithFormat:@"%@",[locationStats objectForKey:@"id"]];
  
//            
//            dispatch_queue_t q = dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_HIGH, 0ul);
//            dispatch_async(q, ^{
            
//                @try {
//                    
//                    
//                    NSDateFormatter * Dateformat2= [[NSDateFormatter alloc] init];
//                    
//                    [Dateformat2 setDateFormat:@"yyyy-MM-dd"];
//                    Dateformat2.timeZone = [NSTimeZone localTimeZone];
//                    
//                    NSString *today=[Dateformat2 stringFromDate:[NSDate date]];
//                    
//                    
//                    NSString *urlString=[[NSString alloc]init];
//                    
//                    NSLog(@"%@",latBck);
//                     NSLog(@"%@",longBck);
//                    
//                    urlString=[NSString stringWithFormat: @"http://api.sunrise-sunset.org/json?lat=%f&lng=%f&formatted=0&date=%@",target.latitude,target.longitude,today];
//                    
//                    NSLog(@"url: %@",urlString);
//                    
//                    urlString=[urlString stringByAddingPercentEncodingWithAllowedCharacters:[NSCharacterSet URLQueryAllowedCharacterSet]];
//                    
//                    NSError *notierror=Nil;
//                    
//                    NSData *resultdata = [[NSData alloc] init];
//                    resultdata=[NSData dataWithContentsOfURL:[NSURL URLWithString:urlString]options:NSDataReadingUncached error:&notierror];
//                    
////                    dispatch_async(dispatch_get_main_queue(), ^{
////                        NSError *notierror=Nil;
////                        
////                        
////                        
////                        
//                    
//                        
//                        
//                        
//                        NSDictionary *resultJson = [[NSDictionary alloc] init];
//                        //
//                        if (resultdata==nil)
//                        {
//                            
//                        }
//                        else
//                        {
//                            resultJson=[NSJSONSerialization JSONObjectWithData:resultdata //1
//                                        
//                                                                       options:kNilOptions
//                                        
//                                                                         error:&notierror];
//                            NSLog(@"response %@",resultJson);
//                            
//                            if ([[resultJson objectForKey:@"status"] isEqualToString:@"OK"] ) {
//                                
//                                NSDictionary *response=[resultJson objectForKey:@"results"];
//                                
//                                
//                                
//                                
//                                NSString *sunriseStr,*sunsetStr;
//                                
//                                  NSDate *startTimeUTC,*sunriseTimeUTC,*sunsetTimeUTC;
//                                
//                                sunriseStr=[[response objectForKey:@"sunrise"] stringByReplacingOccurrencesOfString:@"T" withString:@" "] ;
//                                
//                                sunriseStr=[sunriseStr stringByReplacingOccurrencesOfString:@"+" withString:@" +"] ;
//                                
//                                NSRange lastColon = [sunriseStr rangeOfString:@":" options:NSBackwardsSearch];
//                                
//                                if(lastColon.location != NSNotFound) {
//                                    sunriseStr = [sunriseStr stringByReplacingCharactersInRange:lastColon
//                                                                       withString: @""];
//                                }
//                            
//                                
//                                sunsetStr=[[response objectForKey:@"sunset"] stringByReplacingOccurrencesOfString:@"T" withString:@" "];
//                                
//                                  sunsetStr=[sunsetStr stringByReplacingOccurrencesOfString:@"+" withString:@" +"] ;
//                                
//                               lastColon = [sunsetStr rangeOfString:@":" options:NSBackwardsSearch];
//                                
//                                if(lastColon.location != NSNotFound) {
//                                    sunsetStr = [sunsetStr stringByReplacingCharactersInRange:lastColon
//                                                                                     withString: @""];
//                                }
//                                
//                   
//                                
//                                NSLog(@"%@ and %@ and %@",sunriseStr,sunsetStr,startDate);
//                                
//                            sunriseTimeUTC =[Dateformat dateFromString:sunriseStr];
//                                
//                                sunsetTimeUTC=[Dateformat dateFromString:sunsetStr];
//                                
//                                 startTimeUTC=[Dateformat dateFromString:startDate];
//                                
//                                
//                                NSTimeZone *localTime = [NSTimeZone localTimeZone];
//                                NSLog(@" - current  local timezone  is  %@",[localTime abbreviation]);
//                                
//                                NSDateFormatter * Dateformatt= [[NSDateFormatter alloc] init];
//                                
//                                [Dateformatt setDateFormat:@"yyyy-MM-dd HH:mm:ss"];
//                                Dateformatt.timeZone = [NSTimeZone localTimeZone];
//                                
//                                
//                                
//                                NSString *startLoc,*sunriseLoc,*sunsetLoc;
//                                
//                                startLoc =[Dateformatt stringFromDate:startTimeUTC];
//                                sunriseLoc =[Dateformatt stringFromDate:sunriseTimeUTC];
//                                sunsetLoc =[Dateformatt stringFromDate:sunsetTimeUTC];
//                                
//                                
//                                           NSLog(@"start Time LOC:%@",startLoc);
//                                            NSLog(@"sunrise LOC:%@",sunriseLoc);
//                                            NSLog(@"sunset LOC:%@",sunsetLoc);
//                                
//                                
//                                
//                                _startTimerDate=[Dateformatt dateFromString:startLoc];
//                                
//                                sunriseTime=[Dateformatt dateFromString:sunriseLoc];
//                                
//                                sunsetTime=[Dateformatt dateFromString:sunsetLoc];
//                                
//                                
//                                //startTimerTime=[Dateformatt stringFromDate:startDateTime];
//                                
//                                startTimerTime=startLoc;
//                                
//                                
//                                
//                            }
//                            else {
//                                
//                                
//                                
//                            }
//                            
//                        }
//                   // });
//                }
//                @catch (NSException *exception) {
//                    
//                    
//                    
//                    
//                }
           
    if(weatherFire<10)
            {
                
//                
//          //   NSLog(@"weather: %@",result);
//                
//                                NSLog(@"start Time:%f",[_startTimerDate timeIntervalSince1970]);
//                                NSLog(@"sunrise:%f",[sunriseTime timeIntervalSince1970]);
//                                NSLog(@"sunset:%f",[sunsetTime timeIntervalSince1970]);
//                
//                
//                if([sunriseTime timeIntervalSince1970]>0 && [_startTimerDate timeIntervalSince1970]>0 && [sunsetTime timeIntervalSince1970]>0)
//                {
//                if ([sunriseTime timeIntervalSince1970] < [_startTimerDate timeIntervalSince1970] && [_startTimerDate timeIntervalSince1970] < [sunsetTime timeIntervalSince1970]  ) {
//                    
//
//                    driveTime=@"D";
//                    
//                    NSLog(@"its daytime");
//           
//                }
//                else if ( [_startTimerDate timeIntervalSince1970] < [sunriseTime timeIntervalSince1970]  || [_startTimerDate timeIntervalSince1970] > [sunsetTime timeIntervalSince1970] ) {
//                    
//                    driveTime=@"N";
//                    
//                    NSLog(@"its nightTime");
//
//                    
//                }
//                else{
//                    
//                    driveTime=@"U";
//                    
//                    NSLog(@"Time Unknown");
//                }
//                }
//                else{
//                
//                    driveTime=@"U";
//                    
//                    NSLog(@"Time Unknown");
//                }
                
                NSDictionary *mainStats=[result objectForKey:@"main"];
                
                temperature=[mainStats objectForKey:@"temp"];
                
                
                NSDictionary *weatherDic=[[result objectForKey:@"weather"]objectAtIndex:0];
                
                
                
                weatherId=[NSString stringWithFormat:@"%@",[weatherDic objectForKey:@"id"]];
                
                
                 NSLog(@"weather lat: %f long: %f temp: %@ weather : %@",target.latitude,target.longitude,[mainStats objectForKey:@"temp"],[weatherDic objectForKey:@"description"]);
                
                
                weatherFire++;
                
            }
            
            
            
            
          
            
            
            
            
            
        }
        
        
    }];
    
    
    
    
}

-(void)checkDayTime
{

    NSDateFormatter * Dateformat= [[NSDateFormatter alloc] init];
    
    [Dateformat setDateFormat:@"yyyy-MM-dd HH:mm:ss Z"];
    Dateformat.timeZone = [NSTimeZone timeZoneWithAbbreviation:@"UTC"];
    
    
    
    // target.latitude=-21.15;
    // target.longitude=149.2;
    
    //
    //  target.latitude=-33.87;
    //   target.longitude=151.21;
    
    
    // target.latitude=22.35;
    // target.longitude=88.28;
    
//     target.latitude=-37.81;
//     target.longitude=144.96;
    
    NSLog(@"target lat:%f target Long:%f",target.latitude,target.longitude);
    

            dispatch_queue_t q = dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_HIGH, 0ul);
              dispatch_async(q, ^{
    
    @try {
        
        
        NSDateFormatter * Dateformat2= [[NSDateFormatter alloc] init];
        
        [Dateformat2 setDateFormat:@"yyyy-MM-dd"];
        Dateformat2.timeZone = [NSTimeZone localTimeZone];
        
        NSString *today=[Dateformat2 stringFromDate:[NSDate date]];
        
        
        NSString *urlString=[[NSString alloc]init];
        
        //NSLog(@"%@",latBck);
       // NSLog(@"%@",longBck);
        
        urlString=[NSString stringWithFormat: @"http://api.sunrise-sunset.org/json?lat=%f&lng=%f&formatted=0&date=%@",target.latitude,target.longitude,today];
        
        NSLog(@"url: %@",urlString);
        
        urlString=[urlString stringByAddingPercentEncodingWithAllowedCharacters:[NSCharacterSet URLQueryAllowedCharacterSet]];
        
        NSError *notierror=Nil;
        
        NSData *resultdata = [[NSData alloc] init];
        resultdata=[NSData dataWithContentsOfURL:[NSURL URLWithString:urlString]options:NSDataReadingUncached error:&notierror];
        
                            dispatch_async(dispatch_get_main_queue(), ^{
                                NSError *notierror=Nil;
        
        
        
        
        
        
        
        
        NSDictionary *resultJson = [[NSDictionary alloc] init];
        //
        if (resultdata==nil)
        {
            
        }
        else
        {
            resultJson=[NSJSONSerialization JSONObjectWithData:resultdata //1
                        
                                                       options:kNilOptions
                        
                                                         error:&notierror];
            NSLog(@"response %@",resultJson);
            
            if ([[resultJson objectForKey:@"status"] isEqualToString:@"OK"] ) {
                
                NSDictionary *response=[resultJson objectForKey:@"results"];
                
                
                
                
                NSString *sunriseStr,*sunsetStr;
                
                NSDate *startTimeUTC,*sunriseTimeUTC,*sunsetTimeUTC;
                
                sunriseStr=[[response objectForKey:@"sunrise"] stringByReplacingOccurrencesOfString:@"T" withString:@" "] ;
                
                sunriseStr=[sunriseStr stringByReplacingOccurrencesOfString:@"+" withString:@" +"] ;
                
                NSRange lastColon = [sunriseStr rangeOfString:@":" options:NSBackwardsSearch];
                
                if(lastColon.location != NSNotFound) {
                    sunriseStr = [sunriseStr stringByReplacingCharactersInRange:lastColon
                                                                     withString: @""];
                }
                
                
                sunsetStr=[[response objectForKey:@"sunset"] stringByReplacingOccurrencesOfString:@"T" withString:@" "];
                
                sunsetStr=[sunsetStr stringByReplacingOccurrencesOfString:@"+" withString:@" +"] ;
                
                lastColon = [sunsetStr rangeOfString:@":" options:NSBackwardsSearch];
                
                if(lastColon.location != NSNotFound) {
                    sunsetStr = [sunsetStr stringByReplacingCharactersInRange:lastColon
                                                                   withString: @""];
                }
                
                
                
                NSLog(@"%@ and %@ and %@",sunriseStr,sunsetStr,startDate);
                
                sunriseTimeUTC =[Dateformat dateFromString:sunriseStr];
                
                sunsetTimeUTC=[Dateformat dateFromString:sunsetStr];
                
                startTimeUTC=[Dateformat dateFromString:startDate];
                
                
                NSTimeZone *localTime = [NSTimeZone localTimeZone];
                NSLog(@" - current  local timezone  is  %@",[localTime abbreviation]);
                
                NSDateFormatter * Dateformatt= [[NSDateFormatter alloc] init];
                
                [Dateformatt setDateFormat:@"yyyy-MM-dd HH:mm:ss"];
                Dateformatt.timeZone = [NSTimeZone localTimeZone];
                
                
                
                NSString *startLoc,*sunriseLoc,*sunsetLoc;
                
                startLoc =[Dateformatt stringFromDate:startTimeUTC];
                sunriseLoc =[Dateformatt stringFromDate:sunriseTimeUTC];
                sunsetLoc =[Dateformatt stringFromDate:sunsetTimeUTC];
                
                
                NSLog(@"start Time LOC:%@",startLoc);
                NSLog(@"sunrise LOC:%@",sunriseLoc);
                NSLog(@"sunset LOC:%@",sunsetLoc);
                
                
                
                _startTimerDate=[Dateformatt dateFromString:startLoc];
                
                sunriseTime=[Dateformatt dateFromString:sunriseLoc];
                
                sunsetTime=[Dateformatt dateFromString:sunsetLoc];
                
                
                //startTimerTime=[Dateformatt stringFromDate:startDateTime];
                
                startTimerTime=startLoc;
                
                
                
            }
            else {
                
                
                
            }
            
        }
         });
    }
    @catch (NSException *exception) {
        
        
        
        
    }
              });

    if(dayTimeFire<10)
    {
        
        
        //   NSLog(@"weather: %@",result);
        
        NSLog(@"start Time:%f",[_startTimerDate timeIntervalSince1970]);
        NSLog(@"sunrise:%f",[sunriseTime timeIntervalSince1970]);
        NSLog(@"sunset:%f",[sunsetTime timeIntervalSince1970]);
        
        
        if([sunriseTime timeIntervalSince1970]>0 && [_startTimerDate timeIntervalSince1970]>0 && [sunsetTime timeIntervalSince1970]>0)
        {
            if ([sunriseTime timeIntervalSince1970] < [_startTimerDate timeIntervalSince1970] && [_startTimerDate timeIntervalSince1970] < [sunsetTime timeIntervalSince1970]  ) {
                
                
                driveTime=@"D";
                
                NSLog(@"its daytime");
                
            }
            else if ( [_startTimerDate timeIntervalSince1970] < [sunriseTime timeIntervalSince1970]  || [_startTimerDate timeIntervalSince1970] > [sunsetTime timeIntervalSince1970] ) {
                
                driveTime=@"N";
                
                NSLog(@"its nightTime");
                
                
            }
            else{
                
                driveTime=@"U";
                
                NSLog(@"Time Unknown");
            }
        }
        else{
            
            driveTime=@"U";
            
            NSLog(@"Time Unknown");
        }
        
        
        dayTimeFire++;
        
    }

    
}


- (void)updateTimer
{
    
    
    
    
    [self checkWeather];
    
    [self checkDayTime];
    
    
    timeArr=[[NSArray alloc]init];
    
    // Create date from the elapsed time
    NSDate *currentDate = [NSDate date];
    
    
    
    
    
    
    NSTimeInterval timeInterval = [currentDate timeIntervalSinceDate:self.startTimerDate];
    NSDate *timerDate = [NSDate dateWithTimeIntervalSince1970:timeInterval];
    
    // Create a date formatter
    NSDateFormatter *dateFormatter = [[NSDateFormatter alloc] init];
    [dateFormatter setDateFormat:@"HH:mm:ss"];
    [dateFormatter setTimeZone:[NSTimeZone timeZoneForSecondsFromGMT:0.0]];
    
    // Format the elapsed time and set it to the label
    NSString *timeString = [dateFormatter stringFromDate:timerDate];
    
    NSDateFormatter * Dateformat= [[NSDateFormatter alloc] init];
    
    [Dateformat setDateFormat:@"yyyy-MM-dd HH:mm:ss"];
    Dateformat.timeZone = [NSTimeZone localTimeZone];
    
    
    NSString *currentTime=[Dateformat stringFromDate:currentDate];
    
     currentDate=[Dateformat dateFromString:currentTime];
    
    NSLog(@"start Time:%@",[Dateformat stringFromDate: currentDate]);
    NSLog(@"sunrise:%@",[Dateformat stringFromDate: sunriseTime]);
    NSLog(@"sunset:%@",[Dateformat stringFromDate: sunsetTime]);
    
    

    
    if([sunriseTime timeIntervalSince1970]>0 && [currentDate timeIntervalSince1970]>0 && [sunsetTime timeIntervalSince1970]>0)
    {
        if ([sunriseTime timeIntervalSince1970] < [currentDate timeIntervalSince1970] && [currentDate timeIntervalSince1970] < [sunsetTime timeIntervalSince1970]  ) {
            
        d++;
        
          NSLog(@"daytime:%d", d);
    }
  
    else if ( [currentDate timeIntervalSince1970] < [sunriseTime timeIntervalSince1970]  || [currentDate timeIntervalSince1970] > [sunsetTime timeIntervalSince1970] ) {

        n++;
           NSLog(@"NightTime:%d",n);
        
        
    }
    else{
        

        
        NSLog(@"Time Unknown");
    }
    }
    else{
        
     
        
        NSLog(@"Time Unknown");
    }

    
    
    
    
    // NSLog(@"daytime: %d and nighttime :%d",d,n);
    
    
    
    
    timeArr=[timeString componentsSeparatedByString:@":"];
    
    NSLog(@"time: %@",timeArr);
    
    
    hourLbl.text=timeArr[0];
    minLbl.text=timeArr[1];
    secLbl.text=timeArr[2];
    
    
    NSLog(@"hours driven %@",hourLbl.text);
    
    
    if([hourLbl.text floatValue]>=2) {
        
        alertEnd=[[UIAlertView alloc]initWithTitle:@"Time Up" message:@"Your drive time has exceeded two hours. This drive has been automatically concluded. Please complete your drive conditions." delegate:self cancelButtonTitle:@"OK" otherButtonTitles:nil];
        [alertEnd show];
        
        nodrive=YES;
        
        [self performSelector:@selector(suddenEndDrive) withObject:self afterDelay:0];
        
        
        
    }
    
    NSLog(@" driven hours %f",[hourLbl.text floatValue ]*60);
    NSLog(@" driven minutes %f",[minLbl.text floatValue]);
    
    float  driveHour= [minLbl.text floatValue] + driveHourTemp+([hourLbl.text floatValue ]*60);
    
    NSLog(@"total driven minutes %f",driveHour);
    
    if(driveHour<600)
    {
        backGroundView.image=[UIImage imageNamed:@"red_mode"];
    }
    
    else  if(driveHour>=600 && driveHour<1200)
    {
        backGroundView.image=[UIImage imageNamed:@"pink_mode"];
    }
    
    else if(driveHour>=1200 && driveHour<3000)
    {
        backGroundView.image=[UIImage imageNamed:@"orange_mode"];
    }
    else if(driveHour>=3000 && driveHour<4800)
    {
        backGroundView.image=[UIImage imageNamed:@"yellow_mode"];
    }
    else if(driveHour>=4800 && driveHour<6000)
    {
        backGroundView.image=[UIImage imageNamed:@"blue_mode"];
    }
    else if(driveHour>=6000 && driveHour<7200)
    {
        backGroundView.image=[UIImage imageNamed:@"purple_mode"];
    }
    
    else if (driveHour>=7200)
    {
        backGroundView.image=[UIImage imageNamed:@"green_mode"];
    }
    
    
    //_driver_hours_driven.text = timeString;
}


-(void)Timer_start
{
    
    NSDateFormatter * Dateformat= [[NSDateFormatter alloc] init];
    
    [Dateformat setDateFormat:@"yyyy-MM-dd HH:mm:ss Z"];
    Dateformat.timeZone = [NSTimeZone timeZoneWithAbbreviation:@"UTC"];
    
    self.startTimerDate = [NSDate date];
    
    startDate=[Dateformat stringFromDate:_startTimerDate];
    
    
    
    
    
    
    
    
    
    // Create the stop watch timer that fires every 10 ms
    self.stopWatchTimer = [NSTimer scheduledTimerWithTimeInterval:1.0
                                                           target:self
                                                         selector:@selector(updateTimer)
                                                         userInfo:nil
                                                          repeats:YES];
    
    
    //     _driver_hours_driven.text = [follower routeDurationString];
    
}

//-(void)Timer_start:(Follower *)follower
//{
//
//     _driver_hours_driven.text = [follower routeDurationString];
//
//}
-(void)viewWillAppear:(BOOL)animated
{
    [super viewWillAppear:animated];
    
    
    if ( [[[NSUserDefaults standardUserDefaults]objectForKey:@"driveOn"]boolValue]) {
        
        
        
        NSLog(@"driving");
        
        
        [self checkWeather];
        
        
        [_gmapView addObserver:self forKeyPath:@"myLocation" options:0 context:nil];
        
        //NSLog(@"view will appear.....");
        
        _tracking = YES;
        // [self.follower beginRouteTracking];
        
        
        dateStart = [NSDate date];
        
        
        
        NSDateFormatter *dateFormatter = [[NSDateFormatter alloc] init];
        
        [dateFormatter setDateFormat:@"HH.mm"];
        
        NSString *strCurrentTime = [dateFormatter stringFromDate:[NSDate date]];
        
        //
        //    //NSLog(@" Beginning current time: %f",[strCurrentTime floatValue]);
        //
        //    if ([strCurrentTime floatValue] >= [sunset floatValue] || [strCurrentTime floatValue]  <= [sunrise floatValue]){
        //        //NSLog(@"It's night time");
        //    }else{
        //        //NSLog(@"It's day time");
        //    }
        //
        
        startTime=strCurrentTime;
        
        
    }
    
}


- (IBAction)backtoprev:(id)sender
{
    //    UIStoryboard *story=[UIStoryboard storyboardWithName:@"Main" bundle:nil];
    //
    //    ELActivityLogViewController *activityVC=(ELActivityLogViewController *)[story instantiateViewControllerWithIdentifier:@"activity"];
    //
    //    [self.navigationController pushViewController:activityVC animated:YES];
    
    
    if (isTraffic) {
        [backButton setUserInteractionEnabled:NO];
        [_backImage setHidden:YES];
        [UIView animateWithDuration:0.8 delay:0.0 usingSpringWithDamping:10 initialSpringVelocity:0.5 options:UIViewAnimationOptionCurveEaseIn animations:^{
            
            _parkingView.hidden=NO;
            
            _trafficView.hidden=YES;
            
            
            _parkingView.frame=mainFrame;
            
            
            _parkBtn2.hidden=NO;
            
            _view1Lbl.text=@"Did you practice Parking?";
            
            parkingStrng=@"";
            _parkBtn1.hidden=NO;
            
            
            
            
        } completion:^(BOOL finished) {
            
            isPark=true;
            isTraffic=false;
            isSignal=false;
            
            
        }];
        
    }
    else if (isSignal)
    {
        
        [UIView animateWithDuration:0.8 delay:0.0 usingSpringWithDamping:10 initialSpringVelocity:0.5 options:UIViewAnimationOptionCurveEaseIn animations:^{
            
            _signalView.hidden=YES;
            
            _trafficView.hidden=NO;
            
            
            _trafficView.frame=mainFrame;
            
            _view2Lbl.text=@"Light,Medium or Heavy Traffic?";
            _fourCarBtn.hidden=NO;
            _sixCarBtn.hidden=NO;
            
            trafficStrng=@"";
            
            
            _twoCarBtn.hidden=NO;
            
            
            
        } completion:^(BOOL finished) {
            
            isTraffic=true;
            isSignal=false;
            isPark=false;
            
        }];
        
        
    }
}

- (void)locationManager:(CLLocationManager *)manager didUpdateToLocation:(CLLocation *)newLocation fromLocation:(CLLocation *)oldLocation {
    
    
    //NSLog(@"----Location Manager Delegate calling----");
    
    
    
}


- (void)didReceiveMemoryWarning
{
    [super didReceiveMemoryWarning];
    // Dispose of any resources that can be recreated.
}


- (IBAction)signalBtn:(id)sender
{
    UIButton *tappedBtn=(UIButton *)(id)sender;
    
    if([tappedBtn isEqual:_sealedBtn])
    {
        _view3Lbl.text=@"I  drove on a sealed Road";
        
        _unsealedBtn.hidden=YES;
        _homeBtn.hidden=YES;
        _busyBtn.hidden=YES;
        _multiLaneBtn.hidden=YES;
        
        roadStrng=@"S";
        
        
    }
    else     if([tappedBtn isEqual:_unsealedBtn])
    {
//        _view3Lbl.text=@"I drove on an Unsealed Road";
        _view3Lbl.text= @"I drove on an Unsealed road";
        _sealedBtn.hidden=YES;
        _homeBtn.hidden=YES;
        _busyBtn.hidden=YES;
        _multiLaneBtn.hidden=YES;
        
        roadStrng=@"U";
        
        
    }
    else     if([tappedBtn isEqual:_homeBtn])
    {
        _view3Lbl.text=@"I drove on a Quiet Street";
        
        _sealedBtn.hidden=YES;
        _unsealedBtn.hidden=YES;
        _busyBtn.hidden=YES;
        _multiLaneBtn.hidden=YES;
        
        roadStrng=@"Q";
        
        
    }
    else     if([tappedBtn isEqual:_busyBtn])
    {
        _view3Lbl.text=@"I drove on a Busy road";
        _sealedBtn.hidden=YES;
        _homeBtn.hidden=YES;
        _unsealedBtn.hidden=YES;
        _multiLaneBtn.hidden=YES;
        
        roadStrng=@"B";
        
        
    }
    else     if([tappedBtn isEqual:_multiLaneBtn])
    {
        
        _view3Lbl.text=@"I drove on a Multi-Lane Highway";//Multi-Lane
        
        _sealedBtn.hidden=YES;
        _homeBtn.hidden=YES;
        _busyBtn.hidden=YES;
        _unsealedBtn.hidden=YES;
        
        roadStrng=@"M";
        
        
    }
    
    
    
    
    //    UIGraphicsBeginImageContext(self.view.bounds.size);
    //
    //    [self.view.layer renderInContext:UIGraphicsGetCurrentContext()];
    //
    //    UIImage *viewImage = UIGraphicsGetImageFromCurrentImageContext();
    //
    //    UIGraphicsEndImageContext();
    //
    //    CGRect rect = CGRectMake(backButton.frame.origin.x,backButton.frame.origin.y+backButton.bounds.size.height ,mapDrive.frame.size.width, mapDrive.frame.origin.y+mapBottomBar.frame.origin.y+mapBottomBar.bounds.size.height-(backButton.frame.origin.y+backButton.bounds.size.height ));
    //
    //    CGImageRef imageRef = CGImageCreateWithImageInRect([viewImage CGImage], rect);
    //
    //    UIImage *img = [UIImage imageWithCGImage:imageRef];
    //
    //    //  screenshotImgView.image=img;
    //
    //    NSData *mapImageData=[NSData dataWithData:UIImageJPEGRepresentation(img, 1.0f)];
    //
    //    mapScreenData=mapImageData;
    //
    //    CGImageRelease(imageRef);
    
    //    testScreen.hidden=NO;
    //
    //    screenShot.image=img;
    
    
    //    UIGraphicsBeginImageContext(self.view.bounds.size);
    //
    //    [self.view.layer renderInContext:UIGraphicsGetCurrentContext()];
    //
    //    UIImage *viewImage = UIGraphicsGetImageFromCurrentImageContext();
    //
    //    UIGraphicsEndImageContext();
    //
    //    CGRect rect = CGRectMake(backButton.frame.origin.x,backButton.frame.origin.y+backButton.bounds.size.height ,mapDrive.frame.size.width, mapDrive.frame.origin.y+mapBottomBar.frame.origin.y+mapBottomBar.bounds.size.height-(backButton.frame.origin.y+backButton.bounds.size.height ));
    //
    //    CGImageRef imageRef = CGImageCreateWithImageInRect([viewImage CGImage], rect);
    //
    //    UIImage *img = [UIImage imageWithCGImage:imageRef];
    //
    //    //  screenshotImgView.image=img;
    //
    //    NSData *mapImageData=[NSData dataWithData:UIImageJPEGRepresentation(img, 1.0f)];
    //
    //    mapScreenData=mapImageData;
    //
    //    CGImageRelease(imageRef);
    
    
    
    
    [self performSelector:@selector(signalAction) withObject:nil afterDelay:2.0];
    
    // [self signalAction];
    
    
    
}




-(void)signalAction
{
    
    _sealedBtn.userInteractionEnabled=NO;
    _unsealedBtn.userInteractionEnabled=NO;
    _homeBtn.userInteractionEnabled=NO;
    _busyBtn.userInteractionEnabled=NO;
    _multiLaneBtn.userInteractionEnabled=NO;
    
    
    
    NSArray *sunny=@[@"800",@"904"];
    
    NSArray *fewclouds=@[@"801" ];
    
    NSArray *scatterdlouds=@[@"802" ];
    NSArray *brokenclouds=@[@"803" ];
    NSArray *overcastclouds=@[@"804" ];
    
    NSArray *hail=@[@"906"];
    
    NSArray *snowy=@[@"600",@"601",@"602",@"611",@"612",@"615",@"616",@"620",@"621",@"622",@"903",@"511"];
    
    NSArray *rain=@[@"500",@"501",@"502",@"503",@"504"];
    
    NSArray *thunder=@[@"200",@"201",@"202",@"210",@"211",@"212",@"221",@"230",@"231",@"232",@"900",@"901",@"902"];
    
    NSArray *drizzle=@[@"300",@"301",@"302",@"310",@"311",@"312",@"313",@"314",@"321",@"520",@"521",@"522",@"531"];
    
    NSArray *Atomosphere=@[@"701",@"711",@"721",@"731",@"741",@"751",@"761",@"762",@"771",@"781"];
    
    
    NSString *weatherStr;
    
    if([hail containsObject:weatherId])
    {
        weatherStr=@"H";
        
    }
    else     if([sunny containsObject:weatherId])
    {
        weatherStr=@"S";
    }
    else     if([fewclouds containsObject:weatherId])
    {
        weatherStr=@"FC";
    }
    else     if([scatterdlouds containsObject:weatherId])
    {
        weatherStr=@"SC";
    }
    else     if([brokenclouds containsObject:weatherId])
    {
        weatherStr=@"BC";
    }
    else     if([overcastclouds containsObject:weatherId])
    {
        weatherStr=@"OC";
    }
    else     if([snowy containsObject:weatherId])
    {
        weatherStr=@"W";
    }
    else     if([rain containsObject:weatherId])
    {
        weatherStr=@"R";
    }
    else     if([drizzle containsObject:weatherId])
    {
        weatherStr=@"D";
    }
    else  if([thunder containsObject:weatherId])
    {
        weatherStr=@"T";
    }
    else  if([Atomosphere containsObject:weatherId])
    {
        weatherStr=@"A";
    }
    
    
    
    
    
    // globalOBJ=[[RS_JsonClass alloc]init];
    
    
    
    NSLog(@"map before encode: %@",encodedPath);
    
    
    encodedPath=[encodedPath stringByReplacingOccurrencesOfString:@"\\" withString:@"\\\\"];
    
    NSLog(@"after encode: %@",encodedPath);
    
    
    postData = [NSString stringWithFormat:@"drive_id=%@&parking=%@&traffic=%@&road_condition=%@&weather=%@&state=%@&maproute=%@&temp=%@&user_datetime=%@",driveID,parkingStrng,trafficStrng,roadStrng,weatherStr,stateId, encodedPath,[NSString stringWithFormat:@"%.2f",[temperature floatValue]],startTimerTime];
    
    postData =[postData stringByAddingPercentEscapesUsingEncoding:NSUTF8StringEncoding];
    
    postDict=[[NSMutableDictionary alloc]init];
    
    [postDict setObject:driveID forKey:@"drive_id"];
     [postDict setObject:parkingStrng forKey:@"parking"];
     [postDict setObject:trafficStrng forKey:@"traffic"];
     [postDict setObject:roadStrng forKey:@"road_condition"];
     [postDict setObject:weatherStr forKey:@"weather"];
     [postDict setObject:encodedPath forKey:@"maproute"];
     [postDict setObject:[NSString stringWithFormat:@"%.2f",[temperature floatValue]] forKey:@"temp"];
     [postDict setObject:startTimerTime forKey:@"user_datetime"];
     [postDict setObject:stateId forKey:@"state"];
    
    
    // NSLog(@"after encode: %@",postData);
    
    //    NSString *urlstring=[NSString stringWithFormat:@"%@end_drive.php?%@",App_Domain_Url,postData];
    //
    //    NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:[NSURL URLWithString:urlstring]];
    //
    //    [request setHTTPMethod:@"POST"];
    //
    //    //NSLog(@"Driver id is %@",app.userID);
    //
    //    //    NSString *postData = [NSString stringWithFormat:@"drive_id=%@&parking=%@&traffic=%@&road_condition=%@",driveID,parkingStrng,trafficStrng,roadStrng];//,app.userID
    //
    //    // //NSLog(@"Post data....%@",postData);
    //
    //    // [request setValue:@"application/x-www-form-urlencoded; charset=utf-8" forHTTPHeaderField:@"Content-Type"];
    //    // //NSLog(@"%@",request);
    //
    //    // [request setHTTPBody:[postData dataUsingEncoding:NSUTF8StringEncoding]];
    //
    //
    //    [globalOBJ GlobalDict_Pro_image:[urlstring stringByAddingPercentEscapesUsingEncoding:NSUTF8StringEncoding] Globalstr_image:@"array" globalimage:mapScreenData Withblock:^(id result, NSError *error) {
    //
    //
    //
    //
    //        if(result)
    //        {
    
    
    
    spinnerView.hidden=NO;
    
    
    spinnerView.layer.cornerRadius=9.5f;
    [spinner startAnimating];
    
    [self performSelector:@selector(gotoNextpage) withObject:nil];
    
    
    //NSLog(@"Result after ending drive.....%@",result);
    
    
    // }
    
    
    
    //  }];
    
    
    
    
    
}

-(void)gotoNextpage
{
    
    UIStoryboard *story=[UIStoryboard storyboardWithName:@"Main" bundle:nil];
    
    ELDriveSignatureViewController *activityVC=(ELDriveSignatureViewController *)[story instantiateViewControllerWithIdentifier:@"superSignature"];
    
    activityVC.postData=postData;
    activityVC.mapData=mapScreenData;
    activityVC.postDict=postDict;
    
    
    [self.navigationController pushViewController:activityVC animated:YES];
    
}


- (IBAction)trafficBtn:(id)sender
{
    
    
    UIButton *tappedBtn=(UIButton *)(id)sender;
    
    if([tappedBtn isEqual:_twoCarBtn])
    {
        
        _view2Lbl.text=@"I drove in light traffic";
        _fourCarBtn.hidden=YES;
        _sixCarBtn.hidden=YES;
        
        trafficStrng=@"L";
        
    }
    else     if([tappedBtn isEqual:_fourCarBtn])
    {
        _view2Lbl.text=@"I drove in medium Traffic";
        _twoCarBtn.hidden=YES;
        _sixCarBtn.hidden=YES;
        
        trafficStrng=@"M";
        
        
    }
    else     if([tappedBtn isEqual:_sixCarBtn])
    {
        _view2Lbl.text=@"I drove in heavy Traffic";
        _twoCarBtn.hidden=YES;
        _fourCarBtn.hidden=YES;
        
        trafficStrng=@"H";
        
        
    }
    
    
    [self performSelector:@selector(traficAction) withObject:nil afterDelay:1.5];
    
    
    
}

-(void)traficAction
{
    
    [backButton setUserInteractionEnabled:YES];
    [_backImage setHidden:NO];
    
    [UIView animateWithDuration:0.8 delay:0.0 usingSpringWithDamping:10 initialSpringVelocity:0.5 options:UIViewAnimationOptionCurveEaseIn animations:^{
        
        _trafficView.hidden=YES;
        
        _signalView.hidden=NO;
        
        
        _signalView.frame=mainFrame;
        
        
        
        
        
    } completion:^(BOOL finished) {
        
        isSignal=true;
        isTraffic=false;
        isPark=false;
        
        
    }];
    
    
    
}

- (IBAction)parkBtn:(id)sender
{
    
    
    
    UIButton *tapped=(UIButton *)(id)sender;
    
    if([tapped isEqual:_parkBtn1])
    {
        _parkBtn2.hidden=YES;
        
        _view1Lbl.text=@"I practiced my parking";
        
        parkingStrng=@"T";
        
        
        
    }
    else if ([tapped isEqual:_parkBtn2])
    {
        _parkBtn1.hidden=YES;
        _view1Lbl.text=@"I didn't practice parking";
        
        parkingStrng=@"F";
        
    }
    
    [self performSelector:@selector(parkAction) withObject:nil afterDelay:1.5];
    
    
    
}


-(void)parkAction
{
    
    
    [backButton setUserInteractionEnabled:YES];
    [_backImage setHidden:NO];
    
    
    [UIView animateWithDuration:0.8 delay:0.0 usingSpringWithDamping:10 initialSpringVelocity:0.5 options:UIViewAnimationOptionCurveEaseIn animations:^{
        
        _parkingView.hidden=YES;
        
        _trafficView.hidden=NO;
        
        
        _trafficView.frame=mainFrame;
        
        
        
        
        
    } completion:^(BOOL finished) {
        
        isTraffic=true;
        isPark=false;
        isSignal=false;
        
    }];
    
    
    
}


- (void)followerDidUpdate:(Follower *)follower
{
    
    
    
    dispatch_async(dispatch_get_main_queue(), ^()
                   {
                       
                     
                       
                       if (!driveEnded) {
                           
                  
                           
                           
                           if([[[NSUserDefaults standardUserDefaults] valueForKey:@"unit"] isEqual:@"KM"])
                           {
                               averageSpeed=[follower averageSpeedWithUnit:SpeedUnitKilometersPerHour];
                               _speed.text = [[NSString stringWithFormat:@"%.1f KPH", [follower averageSpeedWithUnit:SpeedUnitKilometersPerHour]]  stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               _driver_avg_speed.text = [[NSString stringWithFormat:@"%.1f KPH", [follower averageSpeedWithUnit:SpeedUnitKilometersPerHour]] stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               
                              // NSLog(@"SPEED------->%@",[NSString stringWithFormat:@"%.1f kph", [follower averageSpeedWithUnit:SpeedUnitMilesPerHour]]);
                               
                              // driveSpeed= _driver_avg_speed.text;
                               
                           }
                           else
                           {
                               averageSpeed=[follower averageSpeedWithUnit:SpeedUnitMilesPerHour];
                               _speed.text = [[NSString stringWithFormat:@"%.1f MPH", [follower averageSpeedWithUnit:SpeedUnitMilesPerHour]]  stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               _driver_avg_speed.text = [[NSString stringWithFormat:@"%.1f MPH", [follower averageSpeedWithUnit:SpeedUnitMilesPerHour]] stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               
                             //  NSLog(@"SPEED------->%@",[NSString stringWithFormat:@"%.1f mph", [follower averageSpeedWithUnit:SpeedUnitMilesPerHour]]);
                               
                             //  driveSpeed= _driver_avg_speed.text;
                               
                               
                           }
                           
                           
                           
                           // _total_time.text = [follower routeDurationString];
                           
                           _driver_hours_driven.text = [follower routeDurationString];
                           
                           //  //NSLog(@"Driver hours driven---->%@", _driver_hours_driven.text);
                           
                           
                           //  hourFragment=[[NSArray alloc]init];
                           
                           //      hourFragment=[_driver_hours_driven.text componentsSeparatedByString:@":"];
                           
                           
                           
                           //                       if([[hourFragment objectAtIndex:0] floatValue]>=10 && [[hourFragment objectAtIndex:0] floatValue]<20)
                           //                       {
                           //                           backGroundView.image=[UIImage imageNamed:@"pink_mode"];
                           //                       }
                           //
                           //                       else if([[hourFragment objectAtIndex:0] floatValue]>=20 && [[hourFragment objectAtIndex:0] floatValue]<50)
                           //                       {
                           //                           backGroundView.image=[UIImage imageNamed:@"orange_mode"];
                           //                       }
                           //                       else if([[hourFragment objectAtIndex:0] floatValue]>=50 && [[hourFragment objectAtIndex:0] floatValue]<80)
                           //                       {
                           //                           backGroundView.image=[UIImage imageNamed:@"yellow_mode"];
                           //                       }
                           //                       else if([[hourFragment objectAtIndex:0] floatValue]>=80 && [[hourFragment objectAtIndex:0] floatValue]<100)
                           //                       {
                           //                           backGroundView.image=[UIImage imageNamed:@"blue_mode"];
                           //                       }
                           //                       else if([[hourFragment objectAtIndex:0] floatValue]>=100 && [[hourFragment objectAtIndex:0] floatValue]<120)
                           //                       {
                           //                           backGroundView.image=[UIImage imageNamed:@"purple_mode"];
                           //                       }
                           //
                           //                       else if ([[hourFragment objectAtIndex:0] floatValue]>=120)
                           //                       {
                           //                           backGroundView.image=[UIImage imageNamed:@"green_mode"];
                           //                       }
                           
                           
                           
                           
                           
                           
                           //  //NSLog(@">>>>>>>>>>>>  %@  >>>>>>>>>",[follower routeDurationString]);
                           
                           // [self startTimer];
                           
                           secLbl.adjustsFontSizeToFitWidth=YES;
                           
                           
                           if([[[NSUserDefaults standardUserDefaults] valueForKey:@"unit"] isEqual:@"KM"])
                           {
                               
                               
                               _total_distance.text = [[NSString stringWithFormat:@"%.2f KMS", [follower totalDistanceWithUnit:DistanceUnitKilometers]]  stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               
                               driveDistance=[[NSString stringWithFormat:@"%.2f", [follower totalDistanceWithUnit:DistanceUnitKilometers]]  stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               
                               _driver_distance_driven.text = [[NSString stringWithFormat:@"%.2f KMS", [follower totalDistanceWithUnit:DistanceUnitKilometers]]  stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               
                               if ([_driver_distance_driven.text isEqualToString:@""] || [_driver_avg_speed.text intValue]==0) {
                                   
                                   _driver_distance_driven.text=@"0.00 KMS";
                                        _total_distance.text=@"0.00 KMS";
                                   driveDistance=@"0.00 KMS";
                               }
                               
                               
                           }
                           else
                           {
                               
                               _total_distance.text = [[NSString stringWithFormat:@"%.2f MILES", [follower totalDistanceWithUnit:DistanceUnitMiles]]  stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               
                               driveDistance=[[NSString stringWithFormat:@"%.2f", [follower totalDistanceWithUnit:DistanceUnitMiles]]  stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               
                               _driver_distance_driven.text = [[NSString stringWithFormat:@"%.2f MILES", [follower totalDistanceWithUnit:DistanceUnitMiles]]  stringByReplacingOccurrencesOfString:@"-" withString:@""];
                               
                               if ([_driver_distance_driven.text isEqualToString:@""] || [_driver_avg_speed.text intValue]==0) {
                                   
                                   _driver_distance_driven.text=@"0.00 MILES";
                                      _total_distance.text=@"0.00 MILES";
                                   driveDistance=@"0.00 KMS";
                                   
                               }
                               
                               
                           }
                           
                           //    self.averageAltitudeView.valueLabel.text = [NSString stringWithFormat:@"%.0f ft", [follower averageAltitudeWithUnit:DistanceUnitFeet]];
                           //    self.maxAltitudeView.valueLabel.text = [NSString stringWithFormat:@"%.0f ft", [follower maximumAltitudeWithUnit:DistanceUnitFeet]];
                           
                           
                       }
                       
                       deviceSpeed = [[[NSString stringWithFormat:@"%.1f", [follower convertedSpeed:follower.speed withUnit:SpeedUnitKilometersPerHour]]  stringByReplacingOccurrencesOfString:@"-" withString:@""]floatValue ];
                       
                       NSLog(@" device speed :%f",deviceSpeed);
                   });
    
    
    //NSLog(@"Drive distance....%@",_driver_distance_driven.text);
    
   // currentdistance=_driver_distance_driven.text;
    
    
    
    
    
    
}




#pragma mark - Map view delegate

- (MKOverlayRenderer *)mapView:(MKMapView *)mapView rendererForOverlay:(id<MKOverlay>)overlay {
    MKPolylineRenderer *renderer = [[MKPolylineRenderer alloc] initWithOverlay:overlay];
    renderer.fillColor = [UIColor colorWithRed:125.0/255.0 green:207.0/255.0 blue:25.0/255.0 alpha:1.0];
    renderer.strokeColor = [UIColor colorWithRed:125.0/255.0 green:207.0/255.0 blue:25.0/255.0 alpha:1.0];
    renderer.lineWidth = 7;
    return renderer;
}



- (void)locationManager:(CLLocationManager *)manager
     didUpdateLocations:(NSArray *)locations
{
    
    
    
    if (locationManager.location.horizontalAccuracy < 0)
    {
        //NSLog(@"----GPS Strength is NIL----");
        
    }
    else if (locationManager.location.horizontalAccuracy > 163)
    {
        //NSLog(@"----GPS Strength is average----");
    }
    else if (locationManager.location.horizontalAccuracy > 48)
    {
        //NSLog(@"----GPS Strength is poor----");
        
    }
    else
    {
        // Full Signal
        
        //NSLog(@"----GPS Strength is STRONG----");
        
        
        
    }
    
    
    
    if (_tracking) {
        
        
        if([[NSString stringWithFormat:@"%f",startLat] isEqualToString:@"0"]  && [[NSString stringWithFormat:@"%f",startLong] isEqualToString:@"0"])
        {
            
            startLat=[[NSString stringWithFormat:@"%f",locationManager.location.coordinate.latitude] floatValue];
            startLong=[[NSString stringWithFormat:@"%f",locationManager.location.coordinate.longitude] floatValue];
            
            
        }
        

        
    }
    
}



-(void)fireURL
{
    
    
    NSLog(@"start Time:%@",startTimerTime);
    
    
    //NSLog(@"Driver ID: %@ \n Super ID: %@ \n Car ID: %@ \n Drive distance: %@ \n Drive speed: %@ \n Start lat: %f \n Start long: %f \n End lat: %@ \n End long: %@ \n",driverID,supID,carID,driveDistance,driveSpeed,startLat,startLong,endLat,endLong);
    
    globalOBJ=[[RS_JsonClass alloc]init];
    
    NSString *urlstring=[NSString stringWithFormat:@"%@drive_summary.php",App_Domain_Url];
    
    NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:[NSURL URLWithString:urlstring]];
    
    [request setHTTPMethod:@"POST"];
    
    //NSLog(@"Driver id is %@",app.userID);
    
    NSArray *speedArray=[[NSArray alloc]init];
    
    speedArray=[_speed.text componentsSeparatedByString:@" "];
    
    
    //Added calculations for licensed supervisors
    
    
  
   // startLat=-31.2532183;
  // startLong=146.921099;
//
    
   // startLat=-27.470125;
 // startLong=153.021072;
    
  // startLat=-19.25;
  //  startLong=146.8;
    
    NSString *postData = [NSString stringWithFormat:@"driver_id=%@&supervisor_id=%@&car_id=%@&total_drive_hr=%@&drive_day_hr=%@&drive_night_hr=%@&day_bonus_time=%@&night_bonus_time=%@&drive_km=%@&avg_speed=%@&start_lat=%f&start_long=%f&end_lat=%f&end_long=%f&odometer=%@&day_time=%@&user_datetime=%@",driverID,supID,carID,totalHours,dayHours,nightHours,[NSString stringWithFormat:@"%d",dayBonusTime],[NSString stringWithFormat:@"%d",nightBonusTime],driveDistance,speedArray[0],startLat,startLong,endLat,endLong,[[NSUserDefaults standardUserDefaults] valueForKey:@"odometer"],driveTime,startTimerTime];//,app.userID
    
//        NSString *postData = [NSString stringWithFormat:@"driver_id=%@&supervisor_id=%@&car_id=%@&total_drive_hr=%@&drive_day_hr=%@&drive_night_hr=%@&drive_km=%@&avg_speed=%@&start_lat=%f&start_long=%f&end_lat=%f&end_long=%f&odometer=%@&day_time=%@&user_datetime=%@",driverID,supID,carID,totalHours,dayHours,nightHours,driveDistance,speedArray[0],startLat,startLong,endLat,endLong,[[NSUserDefaults standardUserDefaults] valueForKey:@"odometer"],driveTime,startTimerTime];//,app.userID
    
    
    NSLog(@"Post data....%@?%@",urlstring,postData);
    
    [request setValue:@"application/x-www-form-urlencoded; charset=utf-8" forHTTPHeaderField:@"Content-Type"];
    //NSLog(@"%@",request);
    
    [request setHTTPBody:[postData dataUsingEncoding:NSUTF8StringEncoding]];
    
    
    [globalOBJ GlobalDict:request Globalstr:@"array" Withblock:^(id result, NSError *error) {
        
        
        if(result)
        {
            
            NSLog(@"Result....%@",result);
            
            if ([[result objectForKey:@"islocation"]boolValue ] && islicensed && [[result objectForKey:@"total_drive_time_without_bonus"] intValue]<600) {
              _total_time.text=   [NSString stringWithFormat:@"%02d:%02d",([totalHours intValue]+dayBonusTime+nightBonusTime)/60,([totalHours intValue]+dayBonusTime+nightBonusTime)%60];
            }
            else{
            
                _total_time.text = [NSString stringWithFormat:@"%02d:%02d",[totalHours intValue]/60,[totalHours intValue]%60];
            }
            
    
            
            if([[[NSUserDefaults standardUserDefaults] valueForKey:@"odometer"] floatValue]>0)
                [[NSUserDefaults standardUserDefaults]setValue:@"0" forKey:@"odometer"];
            
            
            driveID=[NSString stringWithFormat:@"%@",[result valueForKey:@"id"]];
            
            //NSLog(@"**--DRIVE ID--** %@",driveID);
            
            
            
            self.smallProgressIndicator.value = 1;
            
            [self performSelector:@selector(snap) withObject:nil afterDelay:2];
            
            
            // _total_time.text=[NSString stringWithFormat:@"%@:%@",hourLbl.text,minLbl.text];
            _total_time.adjustsFontSizeToFitWidth=YES;
            
            
            
            
            
          
            
        }
        else{
            
            Reachability *networkReachability = [Reachability reachabilityForInternetConnection];
            NetworkStatus networkStatus = [networkReachability currentReachabilityStatus];
            
            if (networkStatus == NotReachable)
            {
                //NSLog(@"There IS NO internet connection");
                
                UIAlertView *networkAlert=[[UIAlertView alloc]initWithTitle:@"Message" message:@"You are not connected to internet" delegate:self cancelButtonTitle:@"OK" otherButtonTitles: nil];
                [networkAlert show];
                
            }
            

        
        }
        
     
        
    }];
    
    
}


- (void)End_Drive_Action
{
    
    
    [_stopWatchTimer invalidate];
    [timer invalidate];
    
    [[NSUserDefaults standardUserDefaults]setBool:false forKey:@"driveOn"];
   // NSLog(@"start:%@",startDateTime);
    
    
    
    // [NSTimer timerWithTimeInterval:0.5 target:self selector:@selector(increase:) userInfo:nil repeats:YES];
    
   // UILongPressGestureRecognizer *gesture=(UILongPressGestureRecognizer *)(id)sender;
    
    if(endPress.state==UIGestureRecognizerStateBegan)
    {
        
        NSDateComponents *components = [[NSCalendar currentCalendar] components:NSCalendarUnitDay | NSCalendarUnitMonth | NSCalendarUnitYear fromDate:[NSDate date]];
        
        
        dayEnd = [components day];
        monthEnd = [components month];
        yearEnd = [components year];
        
      //  NSLog(@"End date..... %ld/%ld/%ld",(long)dayEnd,(long)monthEnd,(long)yearEnd);
        
        
        driveEnded=YES;
        
        @try{
            
            [_gmapView removeObserver:self forKeyPath:@"myLocation"];
            
        }
        @catch(NSException *ex)
        {
            
            NSLog(@"issue removing observer");
            
        }
        encodedPath=[self.follower.path encodedPath];
        
        //NSLog(@"encoded path:%@",encodedPath);
        
        
        GMSPolyline *polyline = [GMSPolyline polylineWithPath:self.follower.path];
        polyline.map = _gmapView;
        polyline.strokeWidth = 5.0f;
        polyline.geodesic = YES;
        polyline.strokeColor=[UIColor colorWithRed:0.0/255.0f green:46.0/255.0f blue:246.0/255.0f alpha:1.0f];
        
        
        [_gmapView animateWithCameraUpdate:[GMSCameraUpdate fitBounds:self.follower.bounds withPadding:80.0f ]];
        
        
        [endDriveButton setUserInteractionEnabled:NO];
        [circleBtn setUserInteractionEnabled:NO];
        [endDriveButton.layer setZPosition:0];
        [circleBtn.layer setZPosition:800];
        
        
        
        [self.follower endRouteTracking];
        
        
        
        NSLog(@"marekr lat: %fmarekr long: %f",self.follower.strLocation.coordinate.latitude,self.follower.strLocation.coordinate.longitude);
        
        
        GMSMarker *marker1 = [[GMSMarker alloc] init];
        
        marker1.position = CLLocationCoordinate2DMake(self.follower.strLocation.coordinate.latitude, self.follower.strLocation.coordinate.longitude);
        marker1.title = @"starting position";
        marker1.icon = [GMSMarker markerImageWithColor:[UIColor greenColor]];
        marker1.map = _gmapView;
        
        GMSMarker *marker2 = [[GMSMarker alloc] init];
        
        marker2.position = CLLocationCoordinate2DMake(self.follower.endLocation.coordinate.latitude, self.follower.endLocation.coordinate.longitude);
        marker2.title = @"End position";
        marker2.icon = [GMSMarker markerImageWithColor:[UIColor redColor]];
        marker2.map = nil;
        
        
        self.markers = [NSSet setWithObjects:marker1, marker2, nil];
        
        
        for(GMSMarker *marker in self.markers) {
            
            marker.map = _gmapView;
            
        }
        
        [mapDrive addOverlay:self.follower.routePolyline level:MKOverlayLevelAboveRoads];
        [mapDrive setRegion:self.follower.routeRegion animated:YES];
        
        
        [UIView animateWithDuration:0
                              delay:0
                            options:UIViewAnimationOptionCurveEaseOut
                         animations:^
         {
             
             // [NSTimer scheduledTimerWithTimeInterval:1.0 target:self selector:@selector(loader) userInfo:nil repeats:YES];
             
             
             // float newValue =self.smallProgressIndicator.value +.25;
             // self.smallProgressIndicator.value = newValue;
             // // self.largeProgressIndicator.value = newValue;
             
             // //NSLog(@"Last value....%f",newValue);
             
             
             
             
         }
                         completion:^(BOOL finished)
         {
             
             [self performSelector:@selector(loader) withObject:nil afterDelay:0.25];
             
             //  mapDrive.showsUserLocation = NO;
             //             mapDrive.delegate = nil;
             //             mapDrive = Nil;
             //             [mapDrive removeFromSuperview];
             
             // [locationManager stopUpdatingLocation];
             
         }];
        
        
        
        
        
    }
}




-(void)loader
{
    
    
    
    
    
    
    
    
    
    float newValue =self.smallProgressIndicator.value + .25;
    self.smallProgressIndicator.value = newValue;
    
    // self.largeProgressIndicator.value = newValue;
    
    //NSLog(@"Last value....%f",newValue);
    
    
    if(newValue<=.50)
        [self performSelector:@selector(loader) withObject:nil afterDelay:0.25];
    
    else if (newValue>.50)
    {
        
        
        Reachability *networkReachability = [Reachability reachabilityForInternetConnection];
        NetworkStatus networkStatus = [networkReachability currentReachabilityStatus];
        
        if (networkStatus == NotReachable)
        {
            //NSLog(@"There IS NO internet connection");
            
            UIAlertView *networkAlert=[[UIAlertView alloc]initWithTitle:@"Message" message:@"You are not connected to internet" delegate:self cancelButtonTitle:@"OK" otherButtonTitles: nil];
            [networkAlert show];
            
        }
        
        
        
        
        else
        {
            
          
            
            
            NSLog(@"start Time:%@",startTimerTime);
            
            
            NSDateFormatter *displayformat2=  [[NSDateFormatter alloc] init];
            
            [displayformat2 setDateFormat:@"hh:mm a"];
            displayformat2.timeZone = [NSTimeZone localTimeZone];
            NSString *displayDate2=[displayformat2 stringFromDate:_startTimerDate];
            NSLog(@"display Time:%@",displayDate2);
            
            
            
            NSDateFormatter *displayformat=  [[NSDateFormatter alloc] init];
            
            [displayformat setDateFormat:@"EEEE, dd MMMM yyyy"];
            displayformat.timeZone = [NSTimeZone localTimeZone];
            NSString *displayDate=[displayformat stringFromDate:_startTimerDate];
            NSLog(@"display Date:%@",displayDate);
            
            [_timeLabel setText:displayDate2];
            [_dateLbl setText:displayDate];
            
           // _driver_map.showsUserLocation = NO;
            
           // Follower *mapControl=[[Follower alloc]init];
           //[mapControl stop_location_update];
            
            // [self.follower endRouteTracking];
            
    
//////////////////CALCULATING BACKGROUND TIME
            
            if(![driveDistance isEqualToString:@""])
           {
//               
//                           NSLog(@"check nightsec:%@",[[NSUserDefaults standardUserDefaults]objectForKey:@"nightSec"]);
//                                  NSLog(@"check daysec:%@",[[NSUserDefaults standardUserDefaults]objectForKey:@"daySec"]);
//                
//                if ([[NSUserDefaults standardUserDefaults]objectForKey:@"nightSec"]!=nil) {
//                    
//                    
//         
//                    
//                    n=n+[[[NSUserDefaults standardUserDefaults]objectForKey:@"nightSec"]intValue];
//                    
//                    [[NSUserDefaults standardUserDefaults]removeObjectForKey:@"nightSec"];
//                }
//                
//                
//                
//                
//                
//                
//                if ([[NSUserDefaults standardUserDefaults]objectForKey:@"daySec"]!=nil) {
//                    
//                    
//     
//                    
//                    d=d+[[[NSUserDefaults standardUserDefaults]objectForKey:@"daySec"]intValue];
//                    
//                    [[NSUserDefaults standardUserDefaults]removeObjectForKey:@"daySec"];
//                    
//                    
//                    
//                }
            
                //////############///////////////
                
//calculating lost time
                
         clockhour= ([minLbl.text floatValue]*60)+([hourLbl.text floatValue ]*3600)+[secLbl.text floatValue];
                
             
               
                lostHour=clockhour-(d+n);
                
                
                if ([driveTime isEqualToString:@"D"])
                    
                {
                    d+=lostHour;
                    
                }
                else if ([driveTime isEqualToString:@"N"]){
                    
                    n+=lostHour;
                    
                }
                
                
    ///////###//////////////////
                
                
               int totHr=floor((d+n)/60);
                
                totalHours=[NSString stringWithFormat:@"%d",totHr];
                
                
                
                d=floor(d/60);
                
                dayHours=[NSString stringWithFormat:@"%d",d];
                
                
                n=floor(n/60);
                
                nightHours=[NSString stringWithFormat:@"%d",n];
                
      
       ////////MISCALCULATION HANDLER/////////////
                
                if ([dayHours intValue]==0 && [totalHours intValue]>[nightHours intValue] && [driveTime isEqualToString:@"N"]) {
                    
                          nightHours=totalHours;
                        
                    }
              
                if ([nightHours intValue]==0 && [totalHours intValue]>[dayHours intValue] && [driveTime isEqualToString:@"D"]) {
                    
                 
                          dayHours=totalHours;
                        
                   
                    }
                    
               
                
        //////########//////////
                
                NSLog(@"total hours :%@ dayhours:%@ and night hours : %@",totalHours,dayHours,nightHours);
                
                
                if (islicensed) {
                    dayBonusTime=[dayHours intValue]*2;
                    nightBonusTime=[nightHours intValue]*2;
                    
                }
                else
                {
                    dayBonusTime=0;
                    nightBonusTime=0;
                    
                }
                
                
            
                
                
            }
            else
            {
                
                UIAlertView *driveAlert=[[UIAlertView alloc]initWithTitle:@"Message" message:@"You have not completed any drive." delegate:self cancelButtonTitle:@"OK" otherButtonTitles:nil];
                
                [driveAlert show];
                
            }
            
           // [[NSUserDefaults standardUserDefaults]removeObjectForKey:@"dayTime"];
            [[NSUserDefaults standardUserDefaults]removeObjectForKey:@"daySec"];
            [[NSUserDefaults standardUserDefaults]removeObjectForKey:@"nightSec"];
            
            
            [self performSelector:@selector(fireURL) withObject:nil afterDelay:1];
            
            
        }
        
    }
    
}


-(void)snap
{
    //Image screen shot of map
    
    //NSLog(@"taking screeshot..");
    
      [_drive_base_view setHidden:YES];
    
    UIGraphicsBeginImageContext(self.view.bounds.size);
    
    [self.view.layer renderInContext:UIGraphicsGetCurrentContext()];
    
    UIImage *viewImage = UIGraphicsGetImageFromCurrentImageContext();
    
    UIGraphicsEndImageContext();
    
    CGRect rect = CGRectMake(backButton.frame.origin.x,backButton.frame.origin.y+backButton.bounds.size.height ,_gmapView.frame.size.width, _signalView.frame.origin.y+_signalView.frame.size.height-(backButton.frame.origin.y+backButton.bounds.size.height));
    
    
    
    
    CGImageRef imageRef = CGImageCreateWithImageInRect([viewImage CGImage], rect);
    
    mapImg = [UIImage imageWithCGImage:imageRef];
    
    //  screenshotImgView.image=img;
    
    NSData *mapImageData=[NSData dataWithData:UIImageJPEGRepresentation(mapImg, 1.0f)];
    
    mapScreenData=mapImageData;
    
    
    
    rect=[_totalScreenView frame];
    
    rect.origin.y=rect.origin.y+(60.0/568.0*self.view.frame.size.height);
    rect.size.height=rect.size.height-(110.0/568.0*self.view.frame.size.height);
    
    
    
    
    imageRef = CGImageCreateWithImageInRect([viewImage CGImage], rect);
    
    mapImg = [UIImage imageWithCGImage:imageRef];
    
    
    
    CGImageRelease(imageRef);
    
    
    [fbshareBtn setUserInteractionEnabled:YES];
    [igShareBtn setUserInteractionEnabled:YES];
    
}

-(void)alertView:(UIAlertView *)alertView clickedButtonAtIndex:(NSInteger)buttonIndex
{
    if(![alertView isEqual:alertEnd])
        
    {
        if(buttonIndex==1 || buttonIndex==0)
        {
            
            ELDriveSummaryController *obj=[[UIStoryboard storyboardWithName:@"Main" bundle:nil]instantiateViewControllerWithIdentifier:@"Drive_Summery"];
            [self.navigationController pushViewController:obj animated:YES];
            
            
        }
        
    }
    
    
    
}

- (IBAction)fbshareClick:(id)sender {
    
    ELFBShareViewController *main=[[ELFBShareViewController alloc]init];
    
    
    
    main.shareImg=mapImg;
    
    
    [self presentViewController:main animated:YES completion:^{
        
        
        
    }];
    
    // [self.navigationController pushViewController:main animated:NO];
    
}
- (IBAction)IGShareClick:(id)sender {
    
    
    
    NSURL *instagramURL = [NSURL URLWithString:@"instagram://"];
    if ([[UIApplication sharedApplication] canOpenURL:instagramURL])
    {
        
        UIImageView *drawImage=[[UIImageView alloc]initWithFrame:CGRectMake(0, 0, 640, 640)];
        
        [self.view addSubview:drawImage];
        
        
        
        
        
        drawImage.image=mapImg;
        
        
        UIImage* instaImage = [self thumbnailFromView:drawImage];
        
        [drawImage setHidden:YES];
        
        NSString* imagePath = [NSString stringWithFormat:@"%@/image.igo", [NSSearchPathForDirectoriesInDomains(NSDocumentDirectory, NSUserDomainMask, YES) lastObject]];
        [[NSFileManager defaultManager] removeItemAtPath:imagePath error:nil];
        [UIImagePNGRepresentation(instaImage) writeToFile:imagePath atomically:YES];
        
        _docController = [UIDocumentInteractionController interactionControllerWithURL:[NSURL fileURLWithPath:imagePath]];
        _docController.delegate=self;
        _docController.UTI = @"com.instagram.exclusivegram";
        [_docController presentOpenInMenuFromRect:self.view.frame inView:self.view animated:YES];
    }
    else
    {
        UIAlertView  *alert = [[UIAlertView alloc] initWithTitle:@"Instagram is not installed"
                                                         message:Nil
                                                        delegate:self
                                               cancelButtonTitle:@"OK"  otherButtonTitles:Nil, nil];
        [alert show];
    }
    
    
    
    
    
    
}



- (void)documentInteractionController:(UIDocumentInteractionController *)controller
           didEndSendingToApplication:(NSString *)application
{
    
    //NSLog(@"app name:%@",application);
    
    
    
    app=[[UIApplication sharedApplication]delegate];
    
    
    NSString *urlString =[NSString stringWithFormat:@"http://www.theezylog.com.au/ezylog/ezylog/iosapp/share_count.php?driver_id=%@",app.userID];
    
    opQueue=[[NSOperationQueue alloc]init];
    
    
    
    [opQueue addOperationWithBlock:^{
        
        
        //NSLog(@"requests url: %@",urlString);
        
        NSString *newString1 = [urlString stringByAddingPercentEscapesUsingEncoding:NSUTF8StringEncoding];
        
        
        NSData *signeddataURL1 =[NSData dataWithContentsOfURL:[NSURL URLWithString:newString1]];
        
        [[NSOperationQueue mainQueue]addOperationWithBlock:^{
            
            
            
            if (signeddataURL1 != nil)
            {
                NSError *error=nil;
                
                json = [NSJSONSerialization JSONObjectWithData:signeddataURL1 //1
                        
                                                       options:kNilOptions
                        
                                                         error:&error];
                //NSLog(@"json returns: %@",json);
                
                
                if([[json objectForKey:@"status"]isEqualToString:@"success"])
                {
                    
                    //NSLog(@"share count incresed");
                    
                    
                }
            }
            else{
                UIAlertView  *alert = [[UIAlertView alloc] initWithTitle:@"error in server connection!"
                                                                 message:Nil
                                                                delegate:self
                                                       cancelButtonTitle:@"OK"  otherButtonTitles:Nil, nil];
                [alert show];
                
            }
        }];
        
    }];
    
    
    
    
}



-(UIImage*)thumbnailFromView:(UIView*)_myView{
    return [self thumbnailFromView:_myView withSize:_myView.frame.size];
}

-(UIImage*)thumbnailFromView:(UIView*)_myView withSize:(CGSize)viewsize{
    
    if ([[UIScreen mainScreen] respondsToSelector:@selector(displayLinkWithTarget:selector:)] &&
        ([UIScreen mainScreen].scale == 2.0)) {
        // Retina display
        CGSize newSize = viewsize;
        newSize.height=newSize.height*2;
        newSize.width=newSize.width*2;
        viewsize=newSize;
    }
    
    UIGraphicsBeginImageContext(_myView.bounds.size);
    CGContextRef context = UIGraphicsGetCurrentContext();
    CGContextSetInterpolationQuality(context, kCGInterpolationHigh);
    CGContextSetShouldAntialias(context, YES);
    [_myView.layer renderInContext: context];
    UIImage *image = UIGraphicsGetImageFromCurrentImageContext();
    UIGraphicsEndImageContext();
    
    
    CGSize size = _myView.frame.size;
    CGFloat scale = MAX(viewsize.width / size.width, viewsize.height / size.height);
    
    UIGraphicsBeginImageContext(viewsize);
    CGFloat width = size.width * scale;
    CGFloat height = size.height * scale;
    float dwidth = ((viewsize.width - width) / 2.0f);
    float dheight = ((viewsize.height - height) / 2.0f);
    CGRect rect = CGRectMake(dwidth, dheight, size.width * scale, size.height * scale);
    [image drawInRect:rect];
    UIImage *newimg = UIGraphicsGetImageFromCurrentImageContext();
    UIGraphicsEndImageContext();
    
    return newimg;
}

-(void)targetMethod:(id)sender
{
    
    //NSLog(@"*****  Timer started  *****");
    
    
    
    
    //   NSArray *distance=[[NSArray alloc]init];
    //distance=[_driver_distance_driven.text componentsSeparatedByString:@" "];
    //NSLog(@"Drive distance....%@",_driver_distance_driven.text);
    
   // NSLog(@"Prev distance: %@ & Current distance: %@",prevDistance,currentdistance);
    
    //if([currentdistance isEqualToString:prevDistance] && driveEnds==NO)
    //code changes by saptarshi for testing via speed, instead of km drives to check on every 10 minutes
    
    
       NSLog(@" target device speed :%f",deviceSpeed);
    
    if (deviceSpeed==0.0) {
 
    
//    if (averageSpeed==averageSpeedOld) {
        stopCount++;
        
        NSLog(@"countdown :%d",stopCount);
        
    }else
    {
        stopCount=0;
        
         NSLog(@"countdown :%d",stopCount);
    }
    
    
    if(stopCount==10 && driveEnds==NO)
    {
        
        //NSLog(@"You have not driven from previous 10 minute...");
        
        alertEnd=[[UIAlertView alloc]initWithTitle:@"Sorry" message:@"Your position has been stationary for 10 minutes. This drive has been automatically concluded. Please complete your drive conditions." delegate:self cancelButtonTitle:@"OK" otherButtonTitles:nil];
        [alertEnd show];
        
        nodrive=YES;
        
        [self performSelector:@selector(suddenEndDrive) withObject:self afterDelay:2];
        
    }
 
    
    
    
}



-(void)suddenEndDrive
{
    
    
    if(nodrive==YES)
    {
        //NSLog(@"before break point...");
        
        [timer invalidate];
        [_stopWatchTimer invalidate];
        
        NSDateComponents *components = [[NSCalendar currentCalendar] components:NSCalendarUnitDay | NSCalendarUnitMonth | NSCalendarUnitYear fromDate:[NSDate date]];
        
        
        dayEnd = [components day];
        monthEnd = [components month];
        yearEnd = [components year];
        
        //NSLog(@"End date..... %ld/%ld/%ld",(long)dayEnd,(long)monthEnd,(long)yearEnd);
        
        
        driveEnded=YES;
        
        @try{
            
            [_gmapView removeObserver:self forKeyPath:@"myLocation"];
            
        }
        @catch(NSException *ex)
        {
            
            NSLog(@"issue removing observer");
            
        }
        
        
        encodedPath=[self.follower.path encodedPath];
        
        //NSLog(@"encoded path:%@",encodedPath);
        
        
        GMSPolyline *polyline = [GMSPolyline polylineWithPath:self.follower.path];
        polyline.map = _gmapView;
        polyline.strokeWidth = 5.0f;
        polyline.geodesic = YES;
        polyline.strokeColor=[UIColor colorWithRed:0.0/255.0f green:46.0/255.0f blue:246.0/255.0f alpha:1.0f];
        
        
        [_gmapView animateWithCameraUpdate:[GMSCameraUpdate fitBounds:self.follower.bounds withPadding:80.0f ]];
        
        
        [endDriveButton setUserInteractionEnabled:NO];
        [circleBtn setUserInteractionEnabled:NO];
        
        [self.follower endRouteTracking];
        
        
        
        //NSLog(@"marekr lat: %fmarekr long: %f",self.follower.strLocation.coordinate.latitude,self.follower.strLocation.coordinate.longitude);
        
        
        GMSMarker *marker1 = [[GMSMarker alloc] init];
        
        marker1.position = CLLocationCoordinate2DMake(self.follower.strLocation.coordinate.latitude, self.follower.strLocation.coordinate.longitude);
        marker1.title = @"starting position";
        marker1.icon = [GMSMarker markerImageWithColor:[UIColor greenColor]];
        marker1.map = _gmapView;
        
        GMSMarker *marker2 = [[GMSMarker alloc] init];
        
        marker2.position = CLLocationCoordinate2DMake(self.follower.endLocation.coordinate.latitude, self.follower.endLocation.coordinate.longitude);
        marker2.title = @"End position";
        marker2.icon = [GMSMarker markerImageWithColor:[UIColor redColor]];
        marker2.map = nil;
        
        
        self.markers = [NSSet setWithObjects:marker1, marker2, nil];
        
        
        for(GMSMarker *marker in self.markers) {
            
            marker.map = _gmapView;
            
        }
        
        [mapDrive addOverlay:self.follower.routePolyline level:MKOverlayLevelAboveRoads];
        [mapDrive setRegion:self.follower.routeRegion animated:YES];
        
        
        [UIView animateWithDuration:0
                              delay:0
                            options:UIViewAnimationOptionCurveEaseOut
                         animations:^
         {
             
             // [NSTimer scheduledTimerWithTimeInterval:1.0 target:self selector:@selector(loader) userInfo:nil repeats:YES];
             
             
             // float newValue =self.smallProgressIndicator.value +.25;
             // self.smallProgressIndicator.value = newValue;
             // // self.largeProgressIndicator.value = newValue;
             
             // //NSLog(@"Last value....%f",newValue);
             
             [self performSelector:@selector(loader) withObject:nil afterDelay:0.25];
             
             
         }
                         completion:^(BOOL finished)
         {
             //  mapDrive.showsUserLocation = NO;
             //             mapDrive.delegate = nil;
             //             mapDrive = Nil;
             //             [mapDrive removeFromSuperview];
             
             // [locationManager stopUpdatingLocation];
             
         }];
        
        
        
        
        
    }
    
    
    
}





-(void)viewWillDisappear:(BOOL)animated
{
    
    
    
    
    [[NSUserDefaults standardUserDefaults]setBool:false forKey:@"driveOn"];
    [_stopWatchTimer invalidate];
    [timer invalidate];
    
    //NSLog(@"View will disappear...");
    
    if([[[NSUserDefaults standardUserDefaults] valueForKey:@"odometer"] floatValue]>0)
        [[NSUserDefaults standardUserDefaults]setValue:@"0" forKey:@"odometer"];
    
    
    switch (mapDrive.mapType) {
        case MKMapTypeHybrid:
        {
            mapDrive.mapType = MKMapTypeStandard;
            
            //NSLog(@"type change...");
        }
            
            break;
        case MKMapTypeStandard:
        {
            mapDrive.mapType = MKMapTypeHybrid;
            
            //NSLog(@"type change...");
        }
            
            break;
        default:
            break;
    }
    
    
    
    
    mapDrive.showsUserLocation = NO;
    mapDrive.delegate = nil;
    mapDrive = Nil;
    [mapDrive removeFromSuperview];
    
    // [locationManager stopUpdatingLocation];
    
}




@end
